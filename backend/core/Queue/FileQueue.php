<?php

declare(strict_types=1);

namespace Core\Queue;

use Core\Contracts\QueueInterface;

/**
 * Filesystem queue driver.
 *
 * Each job is a JSON file in a per-queue directory. `pop()` reserves the oldest
 * job by atomically renaming it to a `.reserved` file, preventing two cron
 * workers from grabbing the same job. Intended for shared hosting where a real
 * broker is unavailable.
 */
final class FileQueue implements QueueInterface
{
    private const MAX_ATTEMPTS = 3;

    public function __construct(private string $directory)
    {
        if (!is_dir($this->directory)) {
            @mkdir($this->directory, 0775, true);
        }
    }

    public function push(string $job, array $payload = [], string $queue = 'default'): string
    {
        $dir = $this->queueDir($queue);
        $id  = $this->generateId();

        $record = [
            'id'         => $id,
            'job'        => $job,
            'payload'    => $payload,
            'attempts'   => 0,
            'queued_at'  => gmdate('Y-m-d\TH:i:s\Z'),
        ];

        $json = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        // Timestamp prefix keeps files ordered oldest-first.
        $file = $dir . '/' . sprintf('%d_%s.job', time(), $id);

        @file_put_contents($file, $json === false ? '{}' : $json, LOCK_EX);

        return $id;
    }

    public function pop(string $queue = 'default'): ?array
    {
        $dir   = $this->queueDir($queue);
        $files = glob($dir . '/*.job') ?: [];
        sort($files);

        foreach ($files as $file) {
            $reserved = $file . '.reserved';

            // Atomic reservation: rename succeeds for exactly one worker.
            if (@rename($file, $reserved)) {
                $record = $this->decode($reserved);

                if ($record === null) {
                    @unlink($reserved);
                    continue;
                }

                return [
                    'id'       => (string) $record['id'],
                    'job'      => (string) $record['job'],
                    'payload'  => (array) ($record['payload'] ?? []),
                    'attempts' => (int) ($record['attempts'] ?? 0),
                ];
            }
        }

        return null;
    }

    public function ack(string $id, string $queue = 'default'): void
    {
        foreach ($this->reservedFilesFor($id, $queue) as $file) {
            @unlink($file);
        }
    }

    public function release(string $id, string $queue = 'default'): void
    {
        $dir = $this->queueDir($queue);

        foreach ($this->reservedFilesFor($id, $queue) as $file) {
            $record = $this->decode($file);

            if ($record === null) {
                @unlink($file);
                continue;
            }

            $record['attempts'] = (int) ($record['attempts'] ?? 0) + 1;

            if ($record['attempts'] >= self::MAX_ATTEMPTS) {
                // Bury: keep as a .failed artifact for inspection.
                @rename($file, $dir . '/' . $id . '.failed');
                continue;
            }

            $json = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $back = $dir . '/' . sprintf('%d_%s.job', time(), $id);

            @file_put_contents($back, $json === false ? '{}' : $json, LOCK_EX);
            @unlink($file);
        }
    }

    public function size(string $queue = 'default'): int
    {
        return count(glob($this->queueDir($queue) . '/*.job') ?: []);
    }

    /**
     * Ensure and return a queue's directory.
     */
    private function queueDir(string $queue): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $queue) ?? 'default';
        $dir  = rtrim($this->directory, '/') . '/' . $safe;

        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return $dir;
    }

    /**
     * @return array<int, string>
     */
    private function reservedFilesFor(string $id, string $queue): array
    {
        return glob($this->queueDir($queue) . '/*' . $id . '*.reserved') ?: [];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decode(string $file): ?array
    {
        $contents = @file_get_contents($file);

        if ($contents === false || $contents === '') {
            return null;
        }

        $data = json_decode($contents, true);

        return is_array($data) ? $data : null;
    }

    /**
     * Generate a random job id.
     */
    private function generateId(): string
    {
        return bin2hex(random_bytes(12));
    }
}

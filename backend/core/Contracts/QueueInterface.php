<?php

declare(strict_types=1);

namespace Core\Contracts;

/**
 * Queue contract.
 *
 * Deferred work is pushed as a job name + payload and later processed by a cron
 * worker. The default file driver fits shared hosting; the interface allows a
 * real broker later. No business jobs are defined yet — this is foundation only.
 */
interface QueueInterface
{
    /**
     * Push a job onto a queue.
     *
     * @param string               $job     Job identifier (handler key).
     * @param array<string, mixed> $payload Serializable job data.
     * @param string               $queue   Named queue (default "default").
     * @return string The queued job id.
     */
    public function push(string $job, array $payload = [], string $queue = 'default'): string;

    /**
     * Reserve and return the next available job, or null if the queue is empty.
     *
     * @return array{id: string, job: string, payload: array<string, mixed>, attempts: int}|null
     */
    public function pop(string $queue = 'default'): ?array;

    /**
     * Acknowledge successful processing (remove the job).
     */
    public function ack(string $id, string $queue = 'default'): void;

    /**
     * Release a job back for retry (or bury after max attempts).
     */
    public function release(string $id, string $queue = 'default'): void;

    /**
     * Count pending jobs in a queue.
     */
    public function size(string $queue = 'default'): int;
}

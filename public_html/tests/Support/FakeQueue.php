<?php

declare(strict_types=1);

namespace Tests\Support;

use Core\Contracts\QueueInterface;

/**
 * In-memory queue double that records pushes for assertions. Jobs are stored in
 * FIFO order; pop/ack/release operate on that list.
 */
final class FakeQueue implements QueueInterface
{
    /** @var array<int, array{id: string, job: string, payload: array<string, mixed>, attempts: int}> */
    public array $jobs = [];

    private int $nextId = 1;

    public function push(string $job, array $payload = [], string $queue = 'default'): string
    {
        $id = 'job-' . $this->nextId++;

        $this->jobs[] = ['id' => $id, 'job' => $job, 'payload' => $payload, 'attempts' => 0];

        return $id;
    }

    public function pop(string $queue = 'default'): ?array
    {
        return array_shift($this->jobs);
    }

    public function ack(string $id, string $queue = 'default'): void
    {
        $this->jobs = array_values(array_filter($this->jobs, static fn(array $j): bool => $j['id'] !== $id));
    }

    public function release(string $id, string $queue = 'default'): void
    {
        foreach ($this->jobs as &$job) {
            if ($job['id'] === $id) {
                $job['attempts']++;
            }
        }
    }

    public function size(string $queue = 'default'): int
    {
        return count($this->jobs);
    }
}

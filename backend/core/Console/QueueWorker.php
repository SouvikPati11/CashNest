<?php

declare(strict_types=1);

namespace Core\Console;

use App\Jobs\JobInterface;
use Core\Contracts\ContainerInterface;
use Core\Contracts\LoggerInterface;
use Core\Contracts\QueueInterface;

/**
 * Cron-driven queue worker.
 *
 * Processes a bounded number of jobs per invocation, then exits — the model that
 * suits shared hosting (a per-minute cron re-invokes it). Job names are mapped to
 * classes via a registry; each class must implement JobInterface. Failures are
 * released back to the queue for retry (and eventually buried by the driver).
 */
final class QueueWorker
{
    /**
     * @param array<string, class-string<JobInterface>> $registry Job name => class.
     */
    public function __construct(
        private QueueInterface $queue,
        private ContainerInterface $container,
        private LoggerInterface $logger,
        private array $registry = []
    ) {
    }

    /**
     * Process up to $maxJobs from a queue. Returns the number processed.
     */
    public function work(string $queue = 'default', int $maxJobs = 50): int
    {
        $processed = 0;

        while ($processed < $maxJobs) {
            $job = $this->queue->pop($queue);

            if ($job === null) {
                break;
            }

            $this->process($job, $queue);
            $processed++;
        }

        return $processed;
    }

    /**
     * Execute a single reserved job with error isolation.
     *
     * @param array{id: string, job: string, payload: array<string, mixed>, attempts: int} $job
     */
    private function process(array $job, string $queue): void
    {
        $name = $job['job'];

        if (!isset($this->registry[$name])) {
            $this->logger->warning('Unknown job discarded.', ['job' => $name, 'id' => $job['id']]);
            $this->queue->ack($job['id'], $queue); // Nothing can handle it; drop.
            return;
        }

        try {
            $handler = $this->container->get($this->registry[$name]);

            if (!$handler instanceof JobInterface) {
                throw new \RuntimeException(sprintf('Job "%s" must implement JobInterface.', $name));
            }

            $handler->handle($job['payload'], $this->container);
            $this->queue->ack($job['id'], $queue);
        } catch (\Throwable $e) {
            $this->logger->error('Job failed; releasing for retry.', [
                'job'      => $name,
                'id'       => $job['id'],
                'attempts' => $job['attempts'],
                'error'    => $e->getMessage(),
            ]);

            $this->queue->release($job['id'], $queue);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Jobs;

use Core\Contracts\ContainerInterface;

/**
 * Queue job contract.
 *
 * A job is a unit of deferred work processed by the cron-driven queue worker.
 * Implementations receive their payload and the container (to resolve any
 * dependencies). No concrete jobs exist yet — this is the foundation contract.
 */
interface JobInterface
{
    /**
     * Execute the job.
     *
     * @param array<string, mixed> $payload   Data supplied when the job was queued.
     * @param ContainerInterface   $container  Service container for dependencies.
     */
    public function handle(array $payload, ContainerInterface $container): void;
}

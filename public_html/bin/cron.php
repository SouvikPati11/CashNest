<?php

/**
 * CashNest — Cron entry point.
 *
 * Invoked by the hosting cron scheduler (e.g. once per minute). Boots the
 * application, then runs the queue worker for a bounded number of jobs and
 * exits. Scheduled maintenance tasks (leaderboard recompute, expiry sweeps,
 * notification dispatch) will be registered here as feature modules land.
 *
 * Example cPanel cron entry:
 *   * * * * * /usr/bin/php /home/USER/public_html/bin/cron.php >> /dev/null 2>&1
 *
 * @package CashNest
 */

declare(strict_types=1);

use App\Jobs\JobInterface;
use App\Jobs\SendPushNotificationJob;
use Core\Application;
use Core\Console\QueueWorker;
use Core\Contracts\ContainerInterface;
use Core\Contracts\LoggerInterface;
use Core\Contracts\QueueInterface;

// Only allow execution from the command line (never over HTTP).
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'Forbidden';
    exit(1);
}

$basePath = dirname(__DIR__);

$autoload = $basePath . '/vendor/autoload.php';

if (!is_file($autoload)) {
    fwrite(STDERR, "Dependencies not installed. Run composer install.\n");
    exit(1);
}

require $autoload;

$app       = Application::boot($basePath);
$container = $app->container();

/** @var LoggerInterface $logger */
$logger = $container->get(LoggerInterface::class);

/*
|--------------------------------------------------------------------------
| Job registry
|--------------------------------------------------------------------------
| Maps queue job names to their handler classes. Each handler implements
| App\Jobs\JobInterface and is resolved from the container by the worker.
|
| @var array<string, class-string<JobInterface>> $registry
*/
$registry = [
    SendPushNotificationJob::NAME => SendPushNotificationJob::class,
];

/** @var QueueInterface $queue */
$queue = $container->get(QueueInterface::class);

$worker    = new QueueWorker($queue, $container, $logger, $registry);
$processed = $worker->work('default', 50);

$logger->info('Cron run complete.', ['jobs_processed' => $processed]);

exit(0);

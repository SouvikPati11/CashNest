<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Queue\FileQueue;
use PHPUnit\Framework\TestCase;

final class FileQueueTest extends TestCase
{
    private string $dir;

    private FileQueue $queue;

    protected function setUp(): void
    {
        $this->dir   = sys_get_temp_dir() . '/cashnest-queue-' . bin2hex(random_bytes(6));
        $this->queue = new FileQueue($this->dir);
    }

    protected function tearDown(): void
    {
        $this->rrmdir($this->dir);
    }

    public function testPushIncreasesSize(): void
    {
        $this->queue->push('demo.job', ['x' => 1]);

        self::assertSame(1, $this->queue->size());
    }

    public function testPopReturnsPushedJob(): void
    {
        $this->queue->push('demo.job', ['x' => 42]);

        $job = $this->queue->pop();

        self::assertNotNull($job);
        self::assertSame('demo.job', $job['job']);
        self::assertSame(42, $job['payload']['x']);
    }

    public function testPopReservesSoSizeDrops(): void
    {
        $this->queue->push('demo.job');
        $this->queue->pop();

        self::assertSame(0, $this->queue->size());
    }

    public function testAckRemovesReservedJob(): void
    {
        $this->queue->push('demo.job');
        $job = $this->queue->pop();

        self::assertNotNull($job);
        $this->queue->ack($job['id']);

        self::assertSame(0, $this->queue->size());
        self::assertNull($this->queue->pop());
    }

    public function testReleaseRequeuesForRetry(): void
    {
        $this->queue->push('demo.job');
        $job = $this->queue->pop();

        self::assertNotNull($job);
        $this->queue->release($job['id']);

        // Job is back and its attempt counter incremented.
        $again = $this->queue->pop();
        self::assertNotNull($again);
        self::assertSame(1, $again['attempts']);
    }

    public function testPopReturnsNullOnEmptyQueue(): void
    {
        self::assertNull($this->queue->pop());
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            is_dir($path) ? $this->rrmdir($path) : @unlink($path);
        }

        @rmdir($dir);
    }
}

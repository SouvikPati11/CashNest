<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Cache\FileCache;
use PHPUnit\Framework\TestCase;

final class FileCacheTest extends TestCase
{
    private string $dir;

    private FileCache $cache;

    protected function setUp(): void
    {
        $this->dir   = sys_get_temp_dir() . '/cashnest-cache-' . bin2hex(random_bytes(6));
        $this->cache = new FileCache($this->dir);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->dir);
    }

    public function testPutAndGet(): void
    {
        $this->cache->put('key', 'value', 60);

        self::assertSame('value', $this->cache->get('key'));
        self::assertTrue($this->cache->has('key'));
    }

    public function testGetReturnsDefaultWhenMissing(): void
    {
        self::assertSame('default', $this->cache->get('missing', 'default'));
    }

    public function testExpiredEntryIsPurged(): void
    {
        $this->cache->put('temp', 'x', -1); // already expired

        self::assertFalse($this->cache->has('temp'));
        self::assertNull($this->cache->get('temp'));
    }

    public function testIncrementCounts(): void
    {
        self::assertSame(1, $this->cache->increment('counter', 1, 60));
        self::assertSame(3, $this->cache->increment('counter', 2, 60));
    }

    public function testAddOnlyWhenAbsent(): void
    {
        self::assertTrue($this->cache->add('once', 'a', 60));
        self::assertFalse($this->cache->add('once', 'b', 60));
        self::assertSame('a', $this->cache->get('once'));
    }

    public function testForgetRemovesEntry(): void
    {
        $this->cache->put('gone', 'x', 60);
        $this->cache->forget('gone');

        self::assertFalse($this->cache->has('gone'));
    }

    public function testGcRemovesOnlyExpiredFiles(): void
    {
        $this->cache->put('live', 'a', 60);
        $this->cache->put('dead', 'b', -1); // already expired

        $removed = $this->cache->gc();

        self::assertSame(1, $removed);
        self::assertTrue($this->cache->has('live'));
        self::assertCount(1, glob($this->dir . '/*.cache') ?: []);
    }
}

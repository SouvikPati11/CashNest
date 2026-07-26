<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\RateLimiter;
use Core\Cache\FileCache;
use PHPUnit\Framework\TestCase;

final class RateLimiterTest extends TestCase
{
    private string $dir;

    private RateLimiter $limiter;

    protected function setUp(): void
    {
        $this->dir     = sys_get_temp_dir() . '/cashnest-rl-' . bin2hex(random_bytes(6));
        $this->limiter = new RateLimiter(new FileCache($this->dir));
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->dir);
    }

    public function testAllowsUpToLimitThenBlocks(): void
    {
        $key = 'user:1|GET /x';

        self::assertTrue($this->limiter->attempt($key, 3, 60));
        self::assertTrue($this->limiter->attempt($key, 3, 60));
        self::assertTrue($this->limiter->attempt($key, 3, 60));
        self::assertFalse($this->limiter->attempt($key, 3, 60)); // 4th exceeds
    }

    public function testRemainingDecrements(): void
    {
        $key = 'ip:1|GET /y';

        $this->limiter->attempt($key, 5, 60);
        $this->limiter->attempt($key, 5, 60);

        self::assertSame(3, $this->limiter->remaining($key, 5));
    }

    public function testClearResetsCounter(): void
    {
        $key = 'ip:2|POST /z';

        $this->limiter->attempt($key, 2, 60);
        $this->limiter->clear($key);

        self::assertSame(0, $this->limiter->attempts($key));
    }
}

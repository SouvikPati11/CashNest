<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Env;
use PHPUnit\Framework\TestCase;

final class EnvTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        // Use unique keys so the PHPUnit <env> block never shadows the file.
        $this->file = sys_get_temp_dir() . '/cashnest-env-' . bin2hex(random_bytes(6)) . '.env';
        file_put_contents($this->file, <<<ENV
        # comment line
        CN_TEST_NAME=CashNest
        CN_TEST_BOOL=true
        CN_TEST_EMPTY=
        CN_TEST_QUOTED="hello world"
        CN_TEST_NULL=null
        ENV);
        Env::load($this->file);
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
    }

    public function testReadsPlainValue(): void
    {
        self::assertSame('CashNest', Env::get('CN_TEST_NAME'));
    }

    public function testCastsBooleanLiteral(): void
    {
        self::assertTrue(Env::get('CN_TEST_BOOL'));
    }

    public function testCastsNullLiteral(): void
    {
        self::assertNull(Env::get('CN_TEST_NULL', 'fallback'));
    }

    public function testStripsQuotes(): void
    {
        self::assertSame('hello world', Env::get('CN_TEST_QUOTED'));
    }

    public function testDefaultWhenMissing(): void
    {
        self::assertSame('def', Env::get('DOES_NOT_EXIST', 'def'));
    }

    public function testMissingFileIsIgnored(): void
    {
        Env::load('/no/such/path/.env'); // must not throw
        self::assertTrue(Env::isLoaded());
    }
}

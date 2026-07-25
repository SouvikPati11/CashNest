<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Config;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    public function testGetReturnsTopLevelValue(): void
    {
        $config = new Config(['app' => ['name' => 'CashNest']]);

        self::assertSame(['name' => 'CashNest'], $config->get('app'));
    }

    public function testGetResolvesDotNotation(): void
    {
        $config = new Config([
            'database' => ['connections' => ['mysql' => ['host' => '127.0.0.1']]],
        ]);

        self::assertSame('127.0.0.1', $config->get('database.connections.mysql.host'));
    }

    public function testGetReturnsDefaultWhenMissing(): void
    {
        $config = new Config([]);

        self::assertSame('fallback', $config->get('missing.key', 'fallback'));
    }

    public function testHasDetectsPresence(): void
    {
        $config = new Config(['a' => ['b' => 1]]);

        self::assertTrue($config->has('a.b'));
        self::assertFalse($config->has('a.c'));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Settings;

use App\Services\ThemeService;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryThemeRepository;

final class ThemeServiceTest extends TestCase
{
    private InMemoryThemeRepository $themes;

    private ThemeService $service;

    protected function setUp(): void
    {
        $this->themes  = new InMemoryThemeRepository();
        $this->service = new ThemeService($this->themes);
    }

    public function testReturnsActiveTheme(): void
    {
        $this->themes->active = [
            'name'          => 'Night',
            'primary_color' => '#000000',
            'default_mode'  => 'dark',
            'is_active'     => 1,
        ];

        [$payload, $etag] = $this->service->activeTheme();

        self::assertSame('Night', $payload['name']);
        self::assertSame('dark', $payload['default_mode']);
        self::assertStringStartsWith('theme_', $etag);
    }

    public function testFallsBackWhenNoActiveTheme(): void
    {
        [$payload] = $this->service->activeTheme();

        self::assertSame('Default', $payload['name']);
        self::assertSame('#1E88E5', $payload['primary_color']);
    }
}

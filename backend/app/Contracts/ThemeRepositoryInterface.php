<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Theme repository contract — reads the active `themes` row.
 */
interface ThemeRepositoryInterface
{
    /**
     * The single active theme, or null when none is configured.
     *
     * @return array<string, mixed>|null
     */
    public function activeTheme(): ?array;
}

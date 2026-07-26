<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Theme service contract — serves the active runtime theme.
 */
interface ThemeServiceInterface
{
    /**
     * The active theme payload plus a cache ETag.
     *
     * @return array{0: array<string, mixed>, 1: string}
     */
    public function activeTheme(): array;
}

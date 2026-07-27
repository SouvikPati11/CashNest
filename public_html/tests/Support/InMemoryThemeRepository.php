<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\ThemeRepositoryInterface;

/**
 * In-memory theme repository for DB-free service tests.
 */
final class InMemoryThemeRepository implements ThemeRepositoryInterface
{
    /** @var array<string, mixed>|null */
    public ?array $active = null;

    public function activeTheme(): ?array
    {
        return $this->active;
    }
}

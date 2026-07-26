<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\AppVersionRepositoryInterface;

/**
 * In-memory app version repository for DB-free service tests.
 */
final class InMemoryAppVersionRepository implements AppVersionRepositoryInterface
{
    /** @var array<string, array<string, mixed>> */
    public array $byPlatform = [];

    /**
     * @param array<string, mixed> $data
     */
    public function seed(string $platform, array $data): void
    {
        $this->byPlatform[$platform] = array_merge(['platform' => $platform, 'is_active' => 1], $data);
    }

    public function activeForPlatform(string $platform): ?array
    {
        return $this->byPlatform[$platform] ?? null;
    }
}

<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\RemoteConfigRepositoryInterface;

/**
 * In-memory remote config repository for DB-free service tests. Mirrors the
 * repository's `all`-before-specific ordering so environment overrides apply.
 */
final class InMemoryRemoteConfigRepository implements RemoteConfigRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    public function seed(string $key, string $type, string $value, string $environment = 'all'): void
    {
        $this->rows[] = [
            'config_key'  => $key,
            'value_type'  => $type,
            'value'       => $value,
            'environment' => $environment,
            'is_active'   => 1,
        ];
    }

    public function activeForEnvironment(string $environment): array
    {
        $rows = array_values(array_filter(
            $this->rows,
            static fn(array $r): bool =>
                (int) ($r['is_active'] ?? 1) === 1 && in_array($r['environment'], ['all', $environment], true)
        ));

        usort(
            $rows,
            static fn(array $a, array $b): int => strcmp((string) $a['environment'], (string) $b['environment'])
        );

        return $rows;
    }
}

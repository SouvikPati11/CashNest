<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\OfferwallProviderRepositoryInterface;

final class InMemoryOfferwallProviderRepository implements OfferwallProviderRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $providers = [];

    public function seed(array $data): int
    {
        $id = (int) ($data['id'] ?? (count($this->providers) + 1));
        $this->providers[$id] = array_merge([
            'id'                  => $id,
            'is_active'           => 1,
            'currency_ratio'      => 1.0,
            'sort_order'          => 0,
            'ip_allowlist'        => null,
            'postback_secret_enc' => '',
        ], $data, ['id' => $id]);

        return $id;
    }

    public function activeProviders(): array
    {
        return array_values(array_filter($this->providers, static fn(array $p): bool => (int) $p['is_active'] === 1));
    }

    public function findBySlug(string $slug): ?array
    {
        foreach ($this->providers as $p) {
            if (($p['slug'] ?? null) === $slug) {
                return $p;
            }
        }

        return null;
    }

    public function find(int|string $id): ?array
    {
        return $this->providers[(int) $id] ?? null;
    }
}

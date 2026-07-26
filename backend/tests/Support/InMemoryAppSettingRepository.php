<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\AppSettingRepositoryInterface;

/**
 * In-memory app setting repository for DB-free service tests.
 */
final class InMemoryAppSettingRepository implements AppSettingRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    public function seed(string $key, string $value, string $type = 'string'): void
    {
        $this->rows[] = ['setting_key' => $key, 'setting_value' => $value, 'value_type' => $type];
    }

    public function publicSettings(): array
    {
        return $this->rows;
    }
}

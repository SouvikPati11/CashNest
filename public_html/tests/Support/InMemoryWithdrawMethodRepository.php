<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\WithdrawMethodRepositoryInterface;

/**
 * In-memory withdraw method repository for DB-free service tests.
 */
final class InMemoryWithdrawMethodRepository implements WithdrawMethodRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    private int $nextId = 1;

    /**
     * @param array<string, mixed> $data
     */
    public function seed(array $data): int
    {
        $id = $this->nextId++;

        $this->rows[$id] = array_merge([
            'id'            => $id,
            'name'          => 'UPI',
            'code'          => 'upi',
            'gateway_id'    => null,
            'min_coins'     => 5000,
            'max_coins'     => 100000,
            'fee_percent'   => '0.0000',
            'fee_flat'      => '0.0000',
            'detail_schema' => ['upi_id' => 'string'],
            'icon_url'      => null,
            'is_active'     => 1,
            'sort_order'    => 0,
        ], $data, ['id' => $id]);

        return $id;
    }

    public function activeMethods(): array
    {
        $rows = array_values(array_filter($this->rows, static fn(array $r): bool => (int) $r['is_active'] === 1));

        usort($rows, static fn(array $a, array $b): int => (int) $a['sort_order'] <=> (int) $b['sort_order']);

        return $rows;
    }

    public function findActiveByCode(string $code): ?array
    {
        foreach ($this->rows as $row) {
            if ($row['code'] === $code && (int) $row['is_active'] === 1) {
                return $row;
            }
        }

        return null;
    }
}

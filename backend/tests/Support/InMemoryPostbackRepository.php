<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\PostbackRepositoryInterface;

final class InMemoryPostbackRepository implements PostbackRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $clicks = [];

    /** @var array<int, array<string, mixed>> */
    public array $conversions = [];

    /** @var array<int, array<string, mixed>> */
    public array $logs = [];

    private int $nextClickId = 1;

    private int $nextConversionId = 1;

    private int $nextLogId = 1;

    public function seedClick(array $data): int
    {
        $id = $this->nextClickId++;
        $this->clicks[$id] = $data + ['id' => $id];

        return $id;
    }

    public function createClick(array $data): string
    {
        return (string) $this->seedClick($data);
    }

    public function findClickByToken(string $token): ?array
    {
        foreach ($this->clicks as $click) {
            if (($click['click_token'] ?? null) === $token) {
                return $click;
            }
        }

        return null;
    }

    public function findConversion(int $providerId, string $transactionIdExt): ?array
    {
        foreach ($this->conversions as $c) {
            if ((int) $c['provider_id'] === $providerId && (string) $c['transaction_id_ext'] === $transactionIdExt) {
                return $c;
            }
        }

        return null;
    }

    public function createConversion(array $data): string
    {
        if ($this->findConversion((int) $data['provider_id'], (string) $data['transaction_id_ext']) !== null) {
            throw new \RuntimeException('Duplicate conversion (unique constraint).');
        }

        $id = $this->nextConversionId++;
        $this->conversions[$id] = $data + ['id' => $id];

        return (string) $id;
    }

    public function updateConversion(int $id, array $data): int
    {
        if (!isset($this->conversions[$id])) {
            return 0;
        }
        $this->conversions[$id] = array_merge($this->conversions[$id], $data);

        return 1;
    }

    public function logPostback(array $data): string
    {
        $id = $this->nextLogId++;
        $this->logs[$id] = $data + ['id' => $id];

        return (string) $id;
    }
}

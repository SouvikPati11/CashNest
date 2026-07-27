<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\RandomizerInterface;

/**
 * Weighted random selector.
 *
 * Chooses one item from a pool by relative `weight`, using the injected
 * randomizer (so outcomes are deterministic under test).
 */
final class WeightedPicker
{
    public function __construct(private RandomizerInterface $random)
    {
    }

    /**
     * Pick one row by its `weight` column; null if the pool is empty/zero-weight.
     *
     * @param array<int, array<string, mixed>> $items
     * @return array<string, mixed>|null
     */
    public function pick(array $items): ?array
    {
        $total = 0;
        foreach ($items as $item) {
            $total += max(0, (int) ($item['weight'] ?? 0));
        }

        if ($total <= 0) {
            return null;
        }

        $roll       = $this->random->int(1, $total);
        $cumulative = 0;

        foreach ($items as $item) {
            $cumulative += max(0, (int) ($item['weight'] ?? 0));
            if ($roll <= $cumulative) {
                return $item;
            }
        }

        return $items[array_key_last($items)] ?? null;
    }
}

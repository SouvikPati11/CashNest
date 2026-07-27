<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\RandomizerInterface;

/**
 * Deterministic randomizer for testing weighted selection.
 */
final class FixedRandomizer implements RandomizerInterface
{
    public function __construct(private int $value = 1)
    {
    }

    public function set(int $value): void
    {
        $this->value = $value;
    }

    public function int(int $min, int $max): int
    {
        return max($min, min($this->value, $max));
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\RandomizerInterface;

/**
 * Secure randomness source backed by random_int().
 */
final class RandomGenerator implements RandomizerInterface
{
    public function int(int $min, int $max): int
    {
        if ($min >= $max) {
            return $min;
        }

        return random_int($min, $max);
    }
}

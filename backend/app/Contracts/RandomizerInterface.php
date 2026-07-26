<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Randomness source.
 *
 * Abstracts secure random integer generation so reward outcome selection
 * (scratch prizes, spin segments) is deterministic and assertable in tests.
 */
interface RandomizerInterface
{
    /**
     * Return a cryptographically secure integer in the inclusive range.
     */
    public function int(int $min, int $max): int;
}

<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Home layout service contract — server-driven dynamic home sections.
 */
interface HomeLayoutServiceInterface
{
    /**
     * Ordered, active home sections plus a cache ETag.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: string}
     */
    public function layout(): array;
}

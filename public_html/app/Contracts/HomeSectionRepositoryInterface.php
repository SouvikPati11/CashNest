<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Home section repository contract — reads active `home_sections`.
 */
interface HomeSectionRepositoryInterface
{
    /**
     * Active, in-window home sections ordered by `sort_order`.
     *
     * @return array<int, array<string, mixed>>
     */
    public function activeSections(string $now): array;
}

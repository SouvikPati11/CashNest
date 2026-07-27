<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Offerwall read/click service contract.
 */
interface OfferwallServiceInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function providers(): array;

    /**
     * @param array<string, mixed> $params
     * @return array{items: array<int, array<string, mixed>>, has_more: bool, page: int, limit: int}
     */
    public function offers(array $params): array;

    /**
     * @return array<string, mixed>
     *
     * @throws \App\Exceptions\NotFoundException
     */
    public function offerDetail(int $offerId): array;

    /**
     * @param array<string, mixed> $params
     * @return array{items: array<int, array<string, mixed>>, has_more: bool, page: int, limit: int}
     */
    public function cpaOffers(array $params): array;

    /**
     * @return array<string, mixed>
     *
     * @throws \App\Exceptions\NotFoundException
     */
    public function cpaOfferDetail(int $offerId): array;

    /**
     * Record a click and return the provider redirect URL + attribution token.
     *
     * @return array{redirect_url: string, click_token: string}
     *
     * @throws \App\Exceptions\NotFoundException
     */
    public function recordClick(int $userId, int $offerId, ?string $ip, ?string $userAgent): array;
}

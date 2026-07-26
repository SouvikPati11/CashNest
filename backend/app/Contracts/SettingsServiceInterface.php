<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Settings service contract.
 *
 * Serves and updates a user's preferences (notification toggles, language,
 * theme mode) and exposes the public app configuration plus the combined
 * app-status (version + maintenance) summary.
 */
interface SettingsServiceInterface
{
    /**
     * The user's preferences merged with public app config.
     *
     * @return array<string, mixed>
     */
    public function getSettings(int $userId): array;

    /**
     * Apply a partial preferences update and return the new preferences.
     *
     * @param array<string, mixed> $changes
     * @return array<string, mixed>
     */
    public function updateSettings(int $userId, array $changes): array;

    /**
     * Public app config (version + maintenance summary) for §2.60.
     *
     * @return array<string, mixed>
     */
    public function appStatus(string $platform, ?int $clientVersionCode): array;
}

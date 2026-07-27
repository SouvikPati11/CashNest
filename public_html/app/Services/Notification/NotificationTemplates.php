<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Models\Notification;

/**
 * Notification template registry.
 *
 * Templates are defined in code (there is no `notification_templates` table in
 * the finalised schema). Each template maps a key to a title/body with
 * `{placeholder}` tokens, a category type, and an optional deep link. Rendering
 * substitutes the supplied parameters and returns notification attributes ready
 * for the notification service.
 */
final class NotificationTemplates
{
    /**
     * @var array<string, array{title: string, body: string, type: string, deep_link?: string}>
     */
    private const TEMPLATES = [
        'welcome' => [
            'title'     => 'Welcome to CashNest!',
            'body'      => 'Hi {name}, start completing tasks and earn your first coins today.',
            'type'      => Notification::TYPE_ENGAGEMENT,
            'deep_link' => '/home',
        ],
        'reward_earned' => [
            'title'     => 'Coins credited',
            'body'      => 'You earned {coins} coins from {source}.',
            'type'      => Notification::TYPE_TRANSACTIONAL,
            'deep_link' => '/wallet',
        ],
        'withdrawal_paid' => [
            'title'     => 'Withdrawal paid',
            'body'      => 'Your withdrawal of {amount} has been paid.',
            'type'      => Notification::TYPE_TRANSACTIONAL,
            'deep_link' => '/wallet/withdrawals',
        ],
        'withdrawal_rejected' => [
            'title'     => 'Withdrawal rejected',
            'body'      => 'Your withdrawal was rejected and {coins} coins were returned.',
            'type'      => Notification::TYPE_TRANSACTIONAL,
            'deep_link' => '/wallet/withdrawals',
        ],
        'referral_bonus' => [
            'title'     => 'Referral bonus!',
            'body'      => 'You earned {coins} coins for referring a friend.',
            'type'      => Notification::TYPE_ENGAGEMENT,
            'deep_link' => '/referrals',
        ],
        'account_update' => [
            'title' => 'Account update',
            'body'  => '{message}',
            'type'  => Notification::TYPE_SYSTEM,
        ],
    ];

    public function has(string $key): bool
    {
        return isset(self::TEMPLATES[$key]);
    }

    /**
     * Render a template into notification attributes.
     *
     * @param array<string, mixed> $params
     * @return array{title: string, body: string, type: string, deep_link: ?string}
     *
     * @throws \InvalidArgumentException When the template key is unknown.
     */
    public function render(string $key, array $params = []): array
    {
        if (!isset(self::TEMPLATES[$key])) {
            throw new \InvalidArgumentException(sprintf('Unknown notification template "%s".', $key));
        }

        $template = self::TEMPLATES[$key];

        return [
            'title'     => $this->interpolate($template['title'], $params),
            'body'      => $this->interpolate($template['body'], $params),
            'type'      => $template['type'],
            'deep_link' => $template['deep_link'] ?? null,
        ];
    }

    /**
     * Replace `{token}` occurrences with stringified parameter values.
     *
     * @param array<string, mixed> $params
     */
    private function interpolate(string $text, array $params): string
    {
        return preg_replace_callback(
            '/\{(\w+)\}/',
            static function (array $matches) use ($params): string {
                $value = $params[$matches[1]] ?? null;

                return is_scalar($value) ? (string) $value : $matches[0];
            },
            $text
        ) ?? $text;
    }
}

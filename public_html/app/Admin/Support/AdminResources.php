<?php

declare(strict_types=1);

namespace App\Admin\Support;

/**
 * Registry of generic admin management resources.
 *
 * Each entry maps a resource key to its backing table, display columns, and the
 * columns searched by the list filter. This drives the read-only management
 * browsers (banners, announcements, themes, config, CMS, FAQ, gateways, ad
 * networks, etc.) through one generic controller/service — the table/column
 * names here are the ONLY identifiers ever interpolated into SQL, so they are a
 * trusted server-side allowlist.
 */
final class AdminResources
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private const RESOURCES = [
        'banners' => [
            'title'             => 'Banners',
            'table'             => 'banners',
            'columns'           => ['id', 'title', 'image_url', 'placement', 'action_type', 'action_value', 'sort_order', 'is_active'],
            'search'            => ['title', 'placement'],
            'permission'        => 'content.view',
            'manage_permission' => 'cms.manage',
            'editable'          => ['title', 'image_url', 'placement', 'action_type', 'action_value', 'sort_order', 'is_active'],
            'creatable'         => true,
            'deletable'         => true,
        ],
        'announcements' => [
            'title'      => 'Announcements',
            'table'      => 'announcements',
            'columns'    => ['id', 'title', 'display_type', 'priority', 'is_active'],
            'search'     => ['title'],
            'permission' => 'content.view',
        ],
        'theme' => [
            'title'             => 'Themes',
            'table'             => 'themes',
            'columns'           => ['id', 'name', 'primary_color', 'default_mode', 'is_active'],
            'search'            => ['name'],
            'permission'        => 'content.view',
            'manage_permission' => 'cms.manage',
            // Only the default mode (light/dark/system) + active flag/name are
            // editable; brand colours are owned by the app's design system.
            'editable'          => ['name', 'default_mode', 'is_active'],
        ],
        'home_layout' => [
            'title'             => 'Home Layout',
            'table'             => 'home_sections',
            'columns'           => ['id', 'section_type', 'title', 'sort_order', 'is_active'],
            'search'            => ['section_type', 'title'],
            'permission'        => 'content.view',
            'manage_permission' => 'cms.manage',
            'editable'          => ['section_type', 'title', 'sort_order', 'is_active'],
            'creatable'         => true,
            'deletable'         => true,
        ],
        'cms' => [
            'title'      => 'CMS Pages',
            'table'      => 'cms_pages',
            'columns'    => ['id', 'slug', 'title', 'locale', 'version', 'is_published'],
            'search'     => ['slug', 'title'],
            'permission' => 'content.view',
        ],
        'faq' => [
            'title'      => 'FAQ',
            'table'      => 'faqs',
            'columns'    => ['id', 'question', 'locale', 'sort_order', 'is_published'],
            'search'     => ['question'],
            'permission' => 'content.view',
        ],
        'remote_config' => [
            'title'             => 'Remote Config',
            'table'             => 'remote_configs',
            'columns'           => ['id', 'config_key', 'value_type', 'value', 'environment', 'is_active'],
            'search'            => ['config_key'],
            'permission'        => 'config.view',
            'manage_permission' => 'settings.manage',
            // Only the flag's value + active state are editable; the key, type
            // and environment define the contract the app consumes.
            'editable'          => ['value', 'is_active'],
        ],
        'payment_gateways' => [
            'title'      => 'Payment Gateways',
            'table'      => 'payment_gateways',
            'columns'    => ['id', 'name', 'code', 'is_active'],
            'search'     => ['name', 'code'],
            'permission' => 'gateway.view',
        ],
        'withdraw_methods' => [
            'title'             => 'Withdraw Methods',
            'table'             => 'withdraw_methods',
            'columns'           => ['id', 'name', 'code', 'min_coins', 'max_coins', 'fee_percent', 'fee_flat', 'is_active', 'sort_order'],
            'search'            => ['name', 'code'],
            'permission'        => 'withdraw.view',
            'manage_permission' => 'payments.manage',
            'editable'          => ['name', 'code', 'min_coins', 'max_coins', 'fee_percent', 'fee_flat', 'icon_url', 'is_active', 'sort_order'],
            'creatable'         => true,
            'deletable'         => true,
        ],
        // 'ad_networks' resource is intentionally omitted: in-app ads are not
        // implemented yet, so there must be no admin page for it (re-add when
        // the Ads feature ships).
        'rewards' => [
            'title'             => 'Reward Tasks',
            'table'             => 'tasks',
            'columns'           => ['id', 'title', 'reward_coins', 'is_active'],
            'search'            => ['title'],
            'permission'        => 'rewards.view',
            'manage_permission' => 'rewards.manage',
            'editable'          => ['title', 'reward_coins', 'is_active', 'sort_order'],
        ],
        'reward_checkin' => [
            'title'             => 'Daily Check-in Rewards',
            'table'             => 'checkin_rewards_config',
            'columns'           => ['id', 'day_number', 'coins', 'is_milestone', 'is_active'],
            'search'            => [],
            'permission'        => 'rewards.view',
            'manage_permission' => 'rewards.manage',
            'editable'          => ['day_number', 'coins', 'is_milestone', 'is_active'],
        ],
        'reward_scratch' => [
            'title'             => 'Scratch Card Rewards',
            'table'             => 'scratch_card_config',
            'columns'           => ['id', 'label', 'reward_coins', 'weight', 'daily_limit', 'is_active'],
            'search'            => ['label'],
            'permission'        => 'rewards.view',
            'manage_permission' => 'rewards.manage',
            'editable'          => ['label', 'reward_coins', 'weight', 'daily_limit', 'is_active'],
        ],
        'reward_spin' => [
            'title'             => 'Spin Wheel Segments',
            'table'             => 'spin_wheel_segments',
            'columns'           => ['id', 'label', 'reward_type', 'reward_coins', 'weight', 'position', 'is_active'],
            'search'            => ['label'],
            'permission'        => 'rewards.view',
            'manage_permission' => 'rewards.manage',
            'editable'          => ['label', 'reward_coins', 'weight', 'position', 'is_active'],
        ],
        'offerwall' => [
            'title'      => 'Offerwall Providers',
            'table'      => 'offerwall_providers',
            'columns'    => ['id', 'name', 'slug', 'is_active'],
            'search'     => ['name', 'slug'],
            'permission' => 'offerwall.view',
        ],
        'referral' => [
            'title'      => 'Referrals',
            'table'      => 'referrals',
            'columns'    => ['id', 'referrer_id', 'referee_id', 'status'],
            'search'     => ['status'],
            'permission' => 'referral.view',
        ],
        'notifications' => [
            'title'      => 'Notification Campaigns',
            'table'      => 'notification_campaigns',
            'columns'    => ['id', 'title', 'type', 'status', 'total_sent'],
            'search'     => ['title'],
            'permission' => 'notification.view',
        ],
        'wallet' => [
            'title'      => 'Wallets',
            'table'      => 'wallets',
            'columns'    => ['id', 'user_id', 'coin_balance', 'coin_reserved'],
            'search'     => [],
            'permission' => 'wallet.view',
        ],
    ];

    public function has(string $key): bool
    {
        return isset(self::RESOURCES[$key]);
    }

    public function get(string $key): ?AdminResourceDef
    {
        $row = self::RESOURCES[$key] ?? null;

        if ($row === null) {
            return null;
        }

        $permission = (string) $row['permission'];

        return new AdminResourceDef(
            (string) $row['title'],
            (string) $row['table'],
            array_values(array_map('strval', (array) $row['columns'])),
            array_values(array_map('strval', (array) $row['search'])),
            $permission,
            array_values(array_map('strval', (array) ($row['editable'] ?? []))),
            (string) ($row['manage_permission'] ?? $permission),
            (bool) ($row['creatable'] ?? false),
            (bool) ($row['deletable'] ?? false)
        );
    }
}

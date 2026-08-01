<?php

/**
 * Admin panel configuration.
 *
 * `session_key` namespaces the admin session bag (separate from user JWT auth).
 * `navigation` is the single source of truth for the sidebar and the dashboard
 * section grid — each entry declares the permission required to see it, so RBAC
 * and the UI never drift apart.
 */

declare(strict_types=1);

use Core\Env;

return [
    'session_key'    => 'cashnest_admin',
    'brand'          => Env::get('ADMIN_BRAND', 'CashNest Admin'),
    'items_per_page' => 20,

    /*
     | Sidebar navigation / dashboard sections. Grouped for the sidebar; each
     | item's `permission` gates visibility and route access via RBAC. Generic
     | content/config sections resolve through /admin/r/{key}.
     */
    'navigation' => [
        ['key' => 'dashboard', 'label' => 'Dashboard',
            'path' => '/admin', 'permission' => 'dashboard.view', 'group' => 'Overview'],
        ['key' => 'users', 'label' => 'Users',
            'path' => '/admin/users', 'permission' => 'user.view', 'group' => 'People'],
        ['key' => 'wallet', 'label' => 'Wallets',
            'path' => '/admin/r/wallet', 'permission' => 'wallet.view', 'group' => 'Finance'],
        ['key' => 'withdrawals', 'label' => 'Withdrawals',
            'path' => '/admin/withdrawals', 'permission' => 'withdraw.view', 'group' => 'Finance'],
        ['key' => 'withdraw_methods', 'label' => 'Withdraw Methods',
            'path' => '/admin/r/withdraw_methods', 'permission' => 'withdraw.view', 'group' => 'Finance'],
        // Payment Gateways UI is hidden until the end-to-end payout integration
        // is complete; the backend/table remains for withdrawal settlement.
        ['key' => 'rewards', 'label' => 'Reward Tasks',
            'path' => '/admin/r/rewards', 'permission' => 'reward.view', 'group' => 'Earning'],
        ['key' => 'reward_checkin', 'label' => 'Daily Check-in',
            'path' => '/admin/r/reward_checkin', 'permission' => 'reward.view', 'group' => 'Earning'],
        ['key' => 'reward_scratch', 'label' => 'Scratch Cards',
            'path' => '/admin/r/reward_scratch', 'permission' => 'reward.view', 'group' => 'Earning'],
        ['key' => 'reward_spin', 'label' => 'Spin Wheel',
            'path' => '/admin/r/reward_spin', 'permission' => 'reward.view', 'group' => 'Earning'],
        ['key' => 'offerwall', 'label' => 'Offerwall',
            'path' => '/admin/r/offerwall', 'permission' => 'offerwall.view', 'group' => 'Earning'],
        ['key' => 'referral', 'label' => 'Referrals',
            'path' => '/admin/r/referral', 'permission' => 'referral.view', 'group' => 'Earning'],
        ['key' => 'ad_networks', 'label' => 'Ad Networks',
            'path' => '/admin/r/ad_networks', 'permission' => 'ads.view', 'group' => 'Earning'],
        ['key' => 'notifications', 'label' => 'Notifications',
            'path' => '/admin/r/notifications', 'permission' => 'notification.view', 'group' => 'Engage'],
        ['key' => 'banners', 'label' => 'Banners',
            'path' => '/admin/r/banners', 'permission' => 'content.view', 'group' => 'Content'],
        ['key' => 'announcements', 'label' => 'Announcements',
            'path' => '/admin/r/announcements', 'permission' => 'content.view', 'group' => 'Content'],
        ['key' => 'theme', 'label' => 'Theme',
            'path' => '/admin/r/theme', 'permission' => 'content.view', 'group' => 'Content'],
        ['key' => 'home_layout', 'label' => 'Home Layout',
            'path' => '/admin/r/home_layout', 'permission' => 'content.view', 'group' => 'Content'],
        ['key' => 'cms', 'label' => 'CMS Pages',
            'path' => '/admin/r/cms', 'permission' => 'content.view', 'group' => 'Content'],
        ['key' => 'faq', 'label' => 'FAQ',
            'path' => '/admin/r/faq', 'permission' => 'content.view', 'group' => 'Content'],
        ['key' => 'remote_config', 'label' => 'Remote Config',
            'path' => '/admin/r/remote_config', 'permission' => 'config.view', 'group' => 'System'],
        ['key' => 'reports', 'label' => 'Reports',
            'path' => '/admin/reports', 'permission' => 'report.view', 'group' => 'System'],
        ['key' => 'fraud', 'label' => 'Fraud',
            'path' => '/admin/fraud', 'permission' => 'fraud.view', 'group' => 'System'],
        ['key' => 'audit_logs', 'label' => 'Audit Logs',
            'path' => '/admin/audit-logs', 'permission' => 'audit.view', 'group' => 'System'],
        ['key' => 'backup', 'label' => 'Backup & Restore',
            'path' => '/admin/backup', 'permission' => 'backup.view', 'group' => 'System'],
        ['key' => 'roles', 'label' => 'Roles & Access',
            'path' => '/admin/roles', 'permission' => 'rbac.view', 'group' => 'System'],
        ['key' => 'settings', 'label' => 'Settings',
            'path' => '/admin/settings', 'permission' => 'settings.view', 'group' => 'System'],
    ],
];

<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\Notification;

/**
 * Notification API resource (API_SPECIFICATION.md §2.54).
 *
 * Shapes an inbox row for API responses. The finalised `notifications` schema
 * has no `uuid` column, so the numeric `id` is the public identifier. Internal
 * fields (push_status, campaign_id) are not exposed. `created_at` is ISO-8601 UTC.
 */
final class NotificationResource
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(Notification $notification): array
    {
        return [
            'id'         => $notification->id(),
            'title'      => $notification->get('title'),
            'body'       => $notification->get('body'),
            'type'       => $notification->type(),
            'deep_link'  => $notification->get('deep_link'),
            'image_url'  => $notification->get('image_url'),
            'data'       => $notification->get('data'),
            'is_read'    => $notification->isRead(),
            'created_at' => self::iso($notification->get('created_at')),
        ];
    }

    /**
     * @param list<Notification> $items
     * @return array<int, array<string, mixed>>
     */
    public static function collection(array $items): array
    {
        return array_map(static fn(Notification $n): array => self::toArray($n), $items);
    }

    private static function iso(mixed $value): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        $timestamp = strtotime($value . ' UTC');

        return $timestamp === false ? $value : gmdate('Y-m-d\TH:i:s\Z', $timestamp);
    }
}

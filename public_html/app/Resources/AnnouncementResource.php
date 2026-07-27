<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\Announcement;

/**
 * Announcement API resource (API_SPECIFICATION.md §2.71).
 *
 * Addressed by numeric id (no uuid column in the finalised schema).
 */
final class AnnouncementResource
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(Announcement $announcement): array
    {
        return [
            'id'             => $announcement->id(),
            'title'          => $announcement->get('title'),
            'body'           => $announcement->get('body'),
            'display_type'   => $announcement->get('display_type'),
            'priority'       => (int) $announcement->get('priority', 100),
            'is_dismissible' => (bool) $announcement->get('is_dismissible', true),
            'action_type'    => $announcement->get('action_type'),
            'action_value'   => $announcement->get('action_value'),
        ];
    }
}

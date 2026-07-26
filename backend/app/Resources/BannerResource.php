<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\Banner;

/**
 * Banner API resource (API_SPECIFICATION.md §2.70).
 *
 * The finalised schema has no uuid column, so the numeric id is the public
 * identifier. Internal audience/window fields are not exposed.
 */
final class BannerResource
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(Banner $banner): array
    {
        return [
            'id'           => $banner->id(),
            'title'        => $banner->get('title'),
            'image_url'    => $banner->get('image_url'),
            'action_type'  => $banner->get('action_type'),
            'action_value' => $banner->get('action_value'),
            'sort_order'   => (int) $banner->get('sort_order', 0),
        ];
    }
}

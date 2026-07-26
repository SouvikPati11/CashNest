<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Promotional banner (DATABASE_DESIGN.md §L.1).
 *
 * Fully admin-controlled home/promo banners. Addressed by numeric id (the
 * finalised schema has no uuid column). Ordering is driven by `sort_order`.
 */
final class Banner extends BaseModel
{
    public const ACTION_NONE      = 'none';
    public const ACTION_DEEP_LINK = 'deep_link';
    public const ACTION_URL       = 'url';
    public const ACTION_OFFER     = 'offer';
    public const ACTION_TASK      = 'task';

    protected string $table = 'banners';

    /** @var array<int, string> */
    protected array $fillable = [
        'title',
        'image_url',
        'placement',
        'action_type',
        'action_value',
        'target_audience',
        'sort_order',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    /** @var array<string, string> */
    protected array $casts = [
        'id'              => 'int',
        'sort_order'      => 'int',
        'is_active'       => 'bool',
        'target_audience' => 'json',
    ];

    public function id(): ?int
    {
        $id = $this->get('id');

        return $id === null ? null : (int) $id;
    }
}

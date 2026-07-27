<?php

declare(strict_types=1);

namespace App\Models;

/**
 * FAQ category (DATABASE_DESIGN.md §H.6).
 *
 * Grouping/ordering for FAQ entries.
 */
final class FaqCategory extends BaseModel
{
    protected string $table = 'faq_categories';

    /** @var array<int, string> */
    protected array $fillable = [
        'name',
        'slug',
        'sort_order',
        'is_active',
    ];

    /** @var array<string, string> */
    protected array $casts = [
        'id'         => 'int',
        'sort_order' => 'int',
        'is_active'  => 'bool',
    ];

    public function id(): ?int
    {
        $id = $this->get('id');

        return $id === null ? null : (int) $id;
    }
}

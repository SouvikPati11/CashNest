<?php

declare(strict_types=1);

namespace App\Models;

/**
 * FAQ entry (DATABASE_DESIGN.md §H.5).
 *
 * Published FAQ content grouped under a category and ordered by `sort_order`.
 */
final class Faq extends BaseModel
{
    protected string $table = 'faqs';

    /** @var array<int, string> */
    protected array $fillable = [
        'category_id',
        'question',
        'answer',
        'locale',
        'sort_order',
        'is_published',
    ];

    /** @var array<string, string> */
    protected array $casts = [
        'id'           => 'int',
        'category_id'  => 'int',
        'sort_order'   => 'int',
        'is_published' => 'bool',
    ];
}

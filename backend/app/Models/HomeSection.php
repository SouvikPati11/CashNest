<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Home-screen section (DATABASE_DESIGN.md §L.8).
 *
 * A server-driven, ordered home block enabling a fully dynamic home layout
 * without app releases. Unknown `section_type`s are ignored gracefully by the
 * client (forward-compatible).
 */
final class HomeSection extends BaseModel
{
    protected string $table = 'home_sections';

    /** @var array<int, string> */
    protected array $fillable = [
        'section_type',
        'title',
        'config',
        'sort_order',
        'target_audience',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    /** @var array<string, string> */
    protected array $casts = [
        'id'              => 'int',
        'sort_order'      => 'int',
        'is_active'       => 'bool',
        'config'          => 'json',
        'target_audience' => 'json',
    ];

    public function sectionType(): string
    {
        $type = $this->get('section_type');

        return is_string($type) ? $type : '';
    }
}

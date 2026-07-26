<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\HomeLayoutServiceInterface;
use App\Contracts\HomeSectionRepositoryInterface;
use App\Models\HomeSection;
use App\Resources\HomeSectionResource;

/**
 * Home layout service — serves the server-driven, fully dynamic home layout.
 *
 * Returns active, in-window sections ordered by `sort_order`, each with its
 * block config. Unknown section types are passed through for forward-compatible
 * clients. An ETag supports client caching.
 */
final class HomeLayoutService implements HomeLayoutServiceInterface
{
    public function __construct(private HomeSectionRepositoryInterface $sections)
    {
    }

    public function layout(): array
    {
        $now = gmdate('Y-m-d H:i:s');

        $sections = array_map(
            static fn(array $row): array => HomeSectionResource::toArray(HomeSection::fromRow($row)),
            $this->sections->activeSections($now)
        );

        $etag = 'home_' . substr(hash('sha256', (string) json_encode($sections)), 0, 12);

        return [$sections, $etag];
    }
}

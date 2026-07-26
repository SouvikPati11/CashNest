<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\CmsPageRepositoryInterface;
use Core\Database\Database;

/**
 * CMS page repository — reads the latest published version per slug + locale.
 */
final class CmsPageRepository implements CmsPageRepositoryInterface
{
    public function __construct(private Database $db)
    {
    }

    public function findPublished(string $slug, string $locale): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM `cms_pages`'
            . ' WHERE `slug` = ? AND `locale` = ? AND `is_published` = 1'
            . ' ORDER BY `version` DESC LIMIT 1',
            [$slug, $locale]
        );
    }
}

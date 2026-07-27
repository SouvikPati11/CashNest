<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\FaqRepositoryInterface;
use Core\Database\Database;

/**
 * FAQ repository — reads active categories and published FAQ entries.
 */
final class FaqRepository implements FaqRepositoryInterface
{
    public function __construct(private Database $db)
    {
    }

    public function activeCategories(): array
    {
        return $this->db->select(
            'SELECT `id`, `name`, `slug`, `sort_order` FROM `faq_categories`'
            . ' WHERE `is_active` = 1 ORDER BY `sort_order` ASC, `id` ASC'
        );
    }

    public function publishedFaqs(string $locale, ?string $categorySlug): array
    {
        $where    = ['f.`is_published` = 1', 'f.`locale` = ?'];
        $bindings = [$locale];

        if ($categorySlug !== null && $categorySlug !== '') {
            $where[]    = 'c.`slug` = ?';
            $bindings[] = $categorySlug;
        }

        return $this->db->select(
            'SELECT f.`id`, f.`category_id`, f.`question`, f.`answer`, f.`sort_order`'
            . ' FROM `faqs` f'
            . ' LEFT JOIN `faq_categories` c ON c.`id` = f.`category_id`'
            . ' WHERE ' . implode(' AND ', $where)
            . ' ORDER BY f.`sort_order` ASC, f.`id` ASC',
            $bindings
        );
    }
}

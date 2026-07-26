<?php

declare(strict_types=1);

namespace App\Controllers\Settings;

use App\Contracts\CmsServiceInterface;
use App\Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;

/**
 * CMS endpoints (API_SPECIFICATION.md §2.73–2.74). Public.
 *
 * `Accept-Language` selects the locale (falls back to English).
 */
final class CmsController extends BaseController
{
    public function __construct(private CmsServiceInterface $cms)
    {
    }

    public function page(Request $request): Response
    {
        return $this->ok($this->cms->page(
            (string) $request->routeParam('slug'),
            $this->locale($request)
        ));
    }

    public function faq(Request $request): Response
    {
        $category = $this->filter($request, 'category');

        return $this->ok($this->cms->faq($this->locale($request), $category));
    }

    private function locale(Request $request): string
    {
        $header = $request->header('accept-language', 'en') ?? 'en';
        $first  = trim(explode(',', $header)[0]);
        $lang   = strtolower(substr($first, 0, 2));

        return preg_match('/^[a-z]{2}$/', $lang) === 1 ? $lang : 'en';
    }

    private function filter(Request $request, string $key): ?string
    {
        $filter = $request->query('filter');
        $value  = is_array($filter) ? ($filter[$key] ?? null) : null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}

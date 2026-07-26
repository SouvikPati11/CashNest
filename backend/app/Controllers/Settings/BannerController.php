<?php

declare(strict_types=1);

namespace App\Controllers\Settings;

use App\Contracts\BannerServiceInterface;
use App\Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Banner endpoint (API_SPECIFICATION.md §2.70). Public.
 */
final class BannerController extends BaseController
{
    public function __construct(private BannerServiceInterface $banners)
    {
    }

    public function index(Request $request): Response
    {
        $placement = $this->filter($request, 'placement') ?? 'home_top';

        return $this->ok($this->banners->forPlacement($placement));
    }

    private function filter(Request $request, string $key): ?string
    {
        $filter = $request->query('filter');
        $value  = is_array($filter) ? ($filter[$key] ?? null) : null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}

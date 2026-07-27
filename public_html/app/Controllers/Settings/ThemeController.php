<?php

declare(strict_types=1);

namespace App\Controllers\Settings;

use App\Contracts\ThemeServiceInterface;
use App\Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Theme endpoint (API_SPECIFICATION.md §2.78). Public.
 */
final class ThemeController extends BaseController
{
    public function __construct(private ThemeServiceInterface $theme)
    {
    }

    public function index(Request $request): Response
    {
        [$payload, $etag] = $this->theme->activeTheme();

        return $this->ok($payload, 'OK', 200, ['etag' => $etag]);
    }
}

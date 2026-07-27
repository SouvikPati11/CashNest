<?php

declare(strict_types=1);

namespace App\Controllers\Settings;

use App\Contracts\HomeLayoutServiceInterface;
use App\Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Home layout endpoint (API_SPECIFICATION.md §2.80). JWT-guarded.
 */
final class HomeLayoutController extends BaseController
{
    public function __construct(private HomeLayoutServiceInterface $homeLayout)
    {
    }

    public function index(Request $request): Response
    {
        [$sections, $etag] = $this->homeLayout->layout();

        return $this->ok($sections, 'OK', 200, ['etag' => $etag]);
    }
}

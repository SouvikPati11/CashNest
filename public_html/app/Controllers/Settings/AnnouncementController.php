<?php

declare(strict_types=1);

namespace App\Controllers\Settings;

use App\Contracts\AnnouncementServiceInterface;
use App\Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Announcement endpoints (API_SPECIFICATION.md §2.71–2.72). JWT-guarded.
 *
 * Rows are addressed by numeric id (no uuid column in the finalised schema).
 */
final class AnnouncementController extends BaseController
{
    public function __construct(private AnnouncementServiceInterface $announcements)
    {
    }

    public function index(Request $request): Response
    {
        return $this->ok($this->announcements->activeFor($this->userId($request)));
    }

    public function seen(Request $request): Response
    {
        $result = $this->announcements->markSeen(
            $this->userId($request),
            (int) $request->routeParam('id')
        );

        return $this->ok($result);
    }

    private function userId(Request $request): int
    {
        $userId = $request->attribute('user_id');

        return is_int($userId) ? $userId : (int) $userId;
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers\Reward;

use App\Contracts\CheckinServiceInterface;
use App\Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Daily check-in endpoints (API_SPECIFICATION.md §2.20–2.22).
 */
final class CheckinController extends BaseController
{
    public function __construct(private CheckinServiceInterface $checkin)
    {
    }

    public function status(Request $request): Response
    {
        return $this->ok($this->checkin->status($this->userId($request)));
    }

    public function calendar(Request $request): Response
    {
        return $this->ok($this->checkin->calendar($this->userId($request)));
    }

    public function claim(Request $request): Response
    {
        return $this->ok($this->checkin->claim($this->userId($request)), 'Check-in claimed.');
    }

    private function userId(Request $request): int
    {
        $userId = $request->attribute('user_id');

        return is_int($userId) ? $userId : (int) $userId;
    }
}

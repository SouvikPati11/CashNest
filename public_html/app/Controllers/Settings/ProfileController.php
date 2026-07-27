<?php

declare(strict_types=1);

namespace App\Controllers\Settings;

use App\Contracts\UserServiceInterface;
use App\Controllers\BaseController;
use App\Models\User;
use App\Resources\UserResource;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Authenticated user profile endpoint (GET/PUT /v1/profile).
 *
 * The JWT middleware attaches the resolved User to the request; this controller
 * returns it and applies whitelisted profile updates via the user service.
 */
final class ProfileController extends BaseController
{
    public function __construct(private UserServiceInterface $users)
    {
    }

    /**
     * GET /v1/profile
     */
    public function show(Request $request): Response
    {
        $user = $request->attribute('user');

        if (!$user instanceof User) {
            return $this->ok(null, 'Profile.');
        }

        return $this->ok(UserResource::toArray($user), 'Profile.');
    }

    /**
     * PUT /v1/profile
     */
    public function update(Request $request): Response
    {
        $user = $request->attribute('user');

        if (!$user instanceof User) {
            return $this->ok(null, 'Profile.');
        }

        $uuid = (string) $user->uuid();

        // Only whitelisted fields are applied (UserService::PROFILE_FIELDS guards).
        $updates = $request->only(['name', 'avatar_url', 'country_code', 'locale', 'phone']);

        $updated = $this->users->updateProfile($uuid, $updates) ?? $user;

        return $this->ok(UserResource::toArray($updated), 'Profile updated.');
    }
}

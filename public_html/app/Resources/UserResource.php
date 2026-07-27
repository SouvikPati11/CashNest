<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\User;

/**
 * User API resource.
 *
 * Shapes a User model into the public JSON representation used in auth responses
 * (API_SPECIFICATION.md §2.1). Only safe, client-facing fields are exposed —
 * never internal ids, balances caches, or auth material.
 */
final class UserResource
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(User $user, bool $isNew = false): array
    {
        return [
            'uuid'          => $user->uuid(),
            'name'          => $user->get('name'),
            'email'         => $user->email(),
            'avatar_url'    => $user->get('avatar_url'),
            'referral_code' => $user->get('referral_code'),
            'status'        => $user->status(),
            'is_new'        => $isNew,
        ];
    }
}

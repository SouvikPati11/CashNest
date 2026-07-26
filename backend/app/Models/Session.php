<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Refresh session.
 *
 * Represents a row of `user_sessions` (DATABASE_DESIGN.md §A.4). Stores only the
 * hash of the refresh token (hidden from serialization); the raw token is never
 * persisted.
 */
final class Session extends BaseModel
{
    protected string $table = 'user_sessions';

    protected string $primaryKey = 'id';

    /** @var array<int, string> */
    protected array $fillable = [
        'user_id',
        'device_id',
        'refresh_token_hash',
        'jwt_id',
        'ip_address',
        'user_agent',
        'expires_at',
        'revoked_at',
    ];

    /** @var array<int, string> */
    protected array $hidden = [
        'refresh_token_hash',
    ];

    /** @var array<string, string> */
    protected array $casts = [
        'id'        => 'int',
        'user_id'   => 'int',
        'device_id' => 'int',
    ];

    public function id(): ?int
    {
        $id = $this->get('id');

        return $id === null ? null : (int) $id;
    }

    public function userId(): ?int
    {
        $id = $this->get('user_id');

        return $id === null ? null : (int) $id;
    }

    public function ipAddress(): ?string
    {
        $value = $this->get('ip_address');

        return is_string($value) ? $value : null;
    }

    public function userAgent(): ?string
    {
        $value = $this->get('user_agent');

        return is_string($value) ? $value : null;
    }

    /**
     * Whether the session has been revoked.
     */
    public function isRevoked(): bool
    {
        return $this->get('revoked_at') !== null;
    }

    /**
     * Whether the session's refresh window has passed.
     */
    public function isExpired(int $now): bool
    {
        $expires = $this->get('expires_at');

        if (!is_string($expires) || $expires === '') {
            return true;
        }

        $timestamp = strtotime($expires . ' UTC');

        return $timestamp === false || $timestamp <= $now;
    }
}

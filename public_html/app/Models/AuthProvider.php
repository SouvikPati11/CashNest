<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Authentication provider identity.
 *
 * Represents a row of the `user_auth_providers` table (DATABASE_DESIGN.md §A.2):
 * the link between a user and a login method (Google or email/password). The
 * password hash is hidden from serialization and never leaves the server.
 */
final class AuthProvider extends BaseModel
{
    public const PROVIDER_GOOGLE = 'google';
    public const PROVIDER_EMAIL  = 'email';

    /** Valid providers (mirrors the DB enum). */
    public const PROVIDERS = [
        self::PROVIDER_GOOGLE,
        self::PROVIDER_EMAIL,
    ];

    protected string $table = 'user_auth_providers';

    protected string $primaryKey = 'id';

    /** @var array<int, string> */
    protected array $fillable = [
        'user_id',
        'provider',
        'provider_uid',
        'password_hash',
        'email',
        'is_primary',
        'last_used_at',
    ];

    /** @var array<int, string> */
    protected array $hidden = [
        'password_hash',
    ];

    /** @var array<string, string> */
    protected array $casts = [
        'id'         => 'int',
        'user_id'    => 'int',
        'is_primary' => 'bool',
    ];

    /**
     * The owning user's numeric id.
     */
    public function userId(): ?int
    {
        $id = $this->get('user_id');

        return $id === null ? null : (int) $id;
    }

    /**
     * The provider type (google|email).
     */
    public function provider(): string
    {
        $provider = $this->get('provider');

        return is_string($provider) ? $provider : '';
    }

    /**
     * The stored password hash (email provider only); null otherwise.
     */
    public function passwordHash(): ?string
    {
        $hash = $this->get('password_hash');

        return is_string($hash) ? $hash : null;
    }

    /**
     * Whether this is an email/password identity.
     */
    public function isEmailProvider(): bool
    {
        return $this->provider() === self::PROVIDER_EMAIL;
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Administrator account (DATABASE_DESIGN.md §I.1).
 *
 * Separate credential store from `users`; authenticated via session + CSRF (not
 * JWT), optionally with TOTP 2FA. The encrypted 2FA secret is never serialised.
 */
final class Admin extends BaseModel
{
    public const STATUS_ACTIVE    = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_DISABLED  = 'disabled';

    protected string $table = 'admins';

    /** @var array<int, string> */
    protected array $fillable = [
        'role_id',
        'name',
        'email',
        'password_hash',
        'two_fa_secret_enc',
        'two_fa_enabled',
        'status',
        'last_login_at',
        'last_login_ip',
    ];

    /** @var array<int, string> */
    protected array $hidden = [
        'password_hash',
        'two_fa_secret_enc',
    ];

    /** @var array<string, string> */
    protected array $casts = [
        'id'             => 'int',
        'role_id'        => 'int',
        'two_fa_enabled' => 'bool',
    ];

    public function id(): ?int
    {
        $id = $this->get('id');

        return $id === null ? null : (int) $id;
    }

    public function roleId(): int
    {
        return (int) $this->get('role_id', 0);
    }

    public function email(): string
    {
        $email = $this->get('email');

        return is_string($email) ? $email : '';
    }

    public function status(): string
    {
        $status = $this->get('status');

        return is_string($status) ? $status : self::STATUS_ACTIVE;
    }

    public function isActive(): bool
    {
        return $this->status() === self::STATUS_ACTIVE;
    }

    public function twoFactorEnabled(): bool
    {
        return (bool) $this->get('two_fa_enabled', false);
    }

    /**
     * The stored 2FA secret (base32), if any. Not exposed via serialization.
     */
    public function twoFactorSecret(): string
    {
        $secret = $this->get('two_fa_secret_enc');

        return is_string($secret) ? $secret : '';
    }

    public function passwordHash(): string
    {
        $hash = $this->get('password_hash');

        return is_string($hash) ? $hash : '';
    }
}

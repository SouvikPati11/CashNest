<?php

declare(strict_types=1);

namespace App\Models;

/**
 * User entity/model.
 *
 * Represents a row of the `users` table (DATABASE_DESIGN.md §A.1) — the central
 * end-user account. This is a plain attribute container (persistence lives in the
 * repository, per the architecture). Balance caches, password material, and any
 * authoritative money state are NEVER written through this model directly.
 */
final class User extends BaseModel
{
    public const STATUS_ACTIVE    = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_BANNED    = 'banned';
    public const STATUS_DELETED   = 'deleted';

    /** Valid account statuses (mirrors the DB enum). */
    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_SUSPENDED,
        self::STATUS_BANNED,
        self::STATUS_DELETED,
    ];

    protected string $table = 'users';

    protected string $primaryKey = 'id';

    /** @var array<int, string> */
    protected array $fillable = [
        'uuid',
        'name',
        'email',
        'email_verified_at',
        'phone',
        'avatar_url',
        'referral_code',
        'referred_by',
        'coin_balance_cache',
        'cash_balance_cache',
        'status',
        'country_code',
        'locale',
        'last_login_at',
        'registration_ip',
    ];

    /** @var array<string, string> */
    protected array $casts = [
        'id'                 => 'int',
        'referred_by'        => 'int',
        'coin_balance_cache' => 'int',
    ];

    /**
     * The internal numeric id (or null before persistence).
     */
    public function id(): ?int
    {
        $id = $this->get('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * The public opaque uuid.
     */
    public function uuid(): ?string
    {
        $uuid = $this->get('uuid');

        return is_string($uuid) ? $uuid : null;
    }

    /**
     * The account email, if any.
     */
    public function email(): ?string
    {
        $email = $this->get('email');

        return is_string($email) ? $email : null;
    }

    /**
     * The current account status.
     */
    public function status(): string
    {
        $status = $this->get('status');

        return is_string($status) ? $status : self::STATUS_ACTIVE;
    }

    /**
     * Whether the account is active (able to use the app).
     */
    public function isActive(): bool
    {
        return $this->status() === self::STATUS_ACTIVE;
    }

    /**
     * Whether the account is blocked (suspended or banned).
     */
    public function isBlocked(): bool
    {
        return in_array($this->status(), [self::STATUS_SUSPENDED, self::STATUS_BANNED], true);
    }

    /**
     * Whether the email has been verified.
     */
    public function hasVerifiedEmail(): bool
    {
        return $this->get('email_verified_at') !== null;
    }
}

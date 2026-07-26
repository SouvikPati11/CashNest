<?php

declare(strict_types=1);

namespace App\Models;

/**
 * In-app notification (DATABASE_DESIGN.md §H.1).
 *
 * A per-user inbox row that is also the persistent record of a push. The
 * finalised schema addresses rows by numeric `id` (there is no `uuid` column);
 * the API therefore exposes the numeric id as the public identifier.
 */
final class Notification extends BaseModel
{
    public const TYPE_TRANSACTIONAL = 'transactional';
    public const TYPE_ENGAGEMENT    = 'engagement';
    public const TYPE_PROMOTIONAL   = 'promotional';
    public const TYPE_SYSTEM        = 'system';

    /** All valid notification types (mirrors the DB enum). */
    public const TYPES = [
        self::TYPE_TRANSACTIONAL,
        self::TYPE_ENGAGEMENT,
        self::TYPE_PROMOTIONAL,
        self::TYPE_SYSTEM,
    ];

    public const PUSH_QUEUED  = 'queued';
    public const PUSH_SENT    = 'sent';
    public const PUSH_FAILED  = 'failed';
    public const PUSH_SKIPPED = 'skipped';

    protected string $table = 'notifications';

    /** @var array<int, string> */
    protected array $fillable = [
        'user_id',
        'campaign_id',
        'title',
        'body',
        'type',
        'deep_link',
        'image_url',
        'data',
        'is_read',
        'read_at',
        'push_status',
    ];

    /** @var array<string, string> */
    protected array $casts = [
        'id'          => 'int',
        'user_id'     => 'int',
        'campaign_id' => 'int',
        'is_read'     => 'bool',
        'data'        => 'json',
    ];

    public function id(): ?int
    {
        $id = $this->get('id');

        return $id === null ? null : (int) $id;
    }

    public function userId(): int
    {
        return (int) $this->get('user_id', 0);
    }

    public function type(): string
    {
        $type = $this->get('type');

        return is_string($type) ? $type : self::TYPE_SYSTEM;
    }

    public function pushStatus(): string
    {
        $status = $this->get('push_status');

        return is_string($status) ? $status : self::PUSH_QUEUED;
    }

    public function isRead(): bool
    {
        return (bool) $this->get('is_read', false);
    }

    public function isPromotional(): bool
    {
        return $this->type() === self::TYPE_PROMOTIONAL;
    }
}

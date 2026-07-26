<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Notification campaign (DATABASE_DESIGN.md §H.2).
 *
 * An admin-composed broadcast that fans out into per-user `notifications` rows
 * in chunks (queue/cron friendly). No admin HTTP surface is built in this
 * module — campaigns are created and dispatched through the campaign service.
 */
final class NotificationCampaign extends BaseModel
{
    public const TYPE_ENGAGEMENT  = 'engagement';
    public const TYPE_PROMOTIONAL = 'promotional';
    public const TYPE_SYSTEM      = 'system';

    public const AUDIENCE_ALL     = 'all';
    public const AUDIENCE_SEGMENT = 'segment';
    public const AUDIENCE_SINGLE  = 'single';

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_SENDING   = 'sending';
    public const STATUS_SENT      = 'sent';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_FAILED    = 'failed';

    protected string $table = 'notification_campaigns';

    /** @var array<int, string> */
    protected array $fillable = [
        'title',
        'body',
        'type',
        'audience',
        'audience_filter',
        'deep_link',
        'image_url',
        'scheduled_at',
        'status',
        'total_targeted',
        'total_sent',
        'total_failed',
        'created_by',
    ];

    /** @var array<string, string> */
    protected array $casts = [
        'id'              => 'int',
        'total_targeted'  => 'int',
        'total_sent'      => 'int',
        'total_failed'    => 'int',
        'created_by'      => 'int',
        'audience_filter' => 'json',
    ];

    public function id(): ?int
    {
        $id = $this->get('id');

        return $id === null ? null : (int) $id;
    }

    public function title(): string
    {
        $title = $this->get('title');

        return is_string($title) ? $title : '';
    }

    public function body(): string
    {
        $body = $this->get('body');

        return is_string($body) ? $body : '';
    }

    public function type(): string
    {
        $type = $this->get('type');

        return is_string($type) ? $type : self::TYPE_PROMOTIONAL;
    }

    public function status(): string
    {
        $status = $this->get('status');

        return is_string($status) ? $status : self::STATUS_DRAFT;
    }

    public function audience(): string
    {
        $audience = $this->get('audience');

        return is_string($audience) ? $audience : self::AUDIENCE_ALL;
    }
}

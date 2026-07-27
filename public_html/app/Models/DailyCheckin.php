<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Daily check-in claim (DATABASE_DESIGN.md §C.1).
 */
final class DailyCheckin extends BaseModel
{
    protected string $table = 'daily_checkins';

    /** @var array<int, string> */
    protected array $fillable = ['user_id', 'checkin_date', 'streak_day', 'coins_awarded', 'transaction_id'];

    /** @var array<string, string> */
    protected array $casts = [
        'id'             => 'int',
        'user_id'        => 'int',
        'streak_day'     => 'int',
        'coins_awarded'  => 'int',
        'transaction_id' => 'int',
    ];

    public function streakDay(): int
    {
        return (int) $this->get('streak_day', 0);
    }

    public function checkinDate(): ?string
    {
        $date = $this->get('checkin_date');

        return is_string($date) ? $date : null;
    }
}

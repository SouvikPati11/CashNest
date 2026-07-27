<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Global key–value app setting (DATABASE_DESIGN.md §H.7).
 *
 * Simple typed settings/feature flags. Only `is_public=1` keys are ever exposed
 * to clients; `value` is cast to `value_type`.
 */
final class AppSetting extends BaseModel
{
    public const TYPE_STRING = 'string';
    public const TYPE_INT    = 'int';
    public const TYPE_BOOL   = 'bool';
    public const TYPE_JSON   = 'json';

    protected string $table = 'app_settings';

    /** @var array<int, string> */
    protected array $fillable = [
        'setting_key',
        'setting_value',
        'value_type',
        'group_name',
        'is_public',
    ];

    /** @var array<string, string> */
    protected array $casts = [
        'id'        => 'int',
        'is_public' => 'bool',
    ];

    public function key(): string
    {
        $key = $this->get('setting_key');

        return is_string($key) ? $key : '';
    }

    /**
     * The stored value cast to its declared type.
     */
    public function typedValue(): mixed
    {
        $raw = $this->get('setting_value');
        $raw = is_scalar($raw) ? (string) $raw : '';

        return match ($this->get('value_type')) {
            self::TYPE_INT  => (int) $raw,
            self::TYPE_BOOL => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            self::TYPE_JSON => $raw === '' ? null : (json_decode($raw, true) ?? $raw),
            default         => $raw,
        };
    }
}

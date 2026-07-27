<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Remote config entry (DATABASE_DESIGN.md §L.6).
 *
 * A typed, audience-aware feature flag / config value served read-only to
 * clients. `value` is cast to `value_type` before delivery.
 */
final class RemoteConfig extends BaseModel
{
    public const TYPE_BOOL   = 'bool';
    public const TYPE_INT    = 'int';
    public const TYPE_FLOAT  = 'float';
    public const TYPE_STRING = 'string';
    public const TYPE_JSON   = 'json';

    protected string $table = 'remote_configs';

    /** @var array<int, string> */
    protected array $fillable = [
        'config_key',
        'value_type',
        'value',
        'environment',
        'audience_segment',
        'description',
        'is_active',
    ];

    /** @var array<string, string> */
    protected array $casts = [
        'id'               => 'int',
        'is_active'        => 'bool',
        'audience_segment' => 'json',
    ];

    public function key(): string
    {
        $key = $this->get('config_key');

        return is_string($key) ? $key : '';
    }

    public function valueType(): string
    {
        $type = $this->get('value_type');

        return is_string($type) ? $type : self::TYPE_STRING;
    }

    /**
     * The stored value cast to its declared type.
     */
    public function typedValue(): mixed
    {
        $raw = $this->get('value');
        $raw = is_scalar($raw) ? (string) $raw : '';

        return match ($this->valueType()) {
            self::TYPE_BOOL  => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            self::TYPE_INT   => (int) $raw,
            self::TYPE_FLOAT => (float) $raw,
            self::TYPE_JSON  => $this->decodeJson($raw),
            default          => $raw,
        };
    }

    private function decodeJson(string $raw): mixed
    {
        if ($raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        return $decoded ?? $raw;
    }
}

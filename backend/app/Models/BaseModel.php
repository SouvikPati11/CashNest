<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Base model.
 *
 * A lightweight attribute container (not an active-record ORM — persistence
 * lives in repositories, per the architecture). Provides mass-assignment
 * guarding via `$fillable`, attribute access, and array/JSON serialization with
 * `$hidden` fields removed. Feature models extend this and declare their table,
 * fillable, hidden, and casts.
 *
 * Subclasses MUST keep a constructor compatible with `__construct(array $attributes = [])`
 * so the static factory `fromRow()` can safely instantiate them.
 *
 * @phpstan-consistent-constructor
 */
abstract class BaseModel implements \JsonSerializable
{
    /** Database table name. Subclasses must set this. */
    protected string $table = '';

    /** Primary key column. */
    protected string $primaryKey = 'id';

    /**
     * Mass-assignable attributes.
     *
     * @var array<int, string>
     */
    protected array $fillable = [];

    /**
     * Attributes hidden from array/JSON output (e.g. password_hash).
     *
     * @var array<int, string>
     */
    protected array $hidden = [];

    /**
     * Attribute type casts (column => bool|int|float|json).
     *
     * @var array<string, string>
     */
    protected array $casts = [];

    /**
     * Current attribute values.
     *
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /**
     * Hydrate a model directly from a database row (bypasses fillable, applies
     * casts). Use for reads from trusted sources.
     *
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): static
    {
        $model = new static();

        foreach ($row as $key => $value) {
            $model->attributes[$key] = $model->castValue($key, $value);
        }

        return $model;
    }

    /**
     * Mass-assign only fillable attributes (with casts).
     *
     * @param array<string, mixed> $attributes
     */
    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if ($this->fillable === [] || in_array($key, $this->fillable, true)) {
                $this->attributes[$key] = $this->castValue($key, $value);
            }
        }

        return $this;
    }

    /**
     * Get an attribute value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Set an attribute value (with cast).
     */
    public function set(string $key, mixed $value): static
    {
        $this->attributes[$key] = $this->castValue($key, $value);

        return $this;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getKeyName(): string
    {
        return $this->primaryKey;
    }

    /**
     * Convert to a plain array with hidden fields removed.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = $this->attributes;

        foreach ($this->hidden as $hiddenKey) {
            unset($data[$hiddenKey]);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Apply the declared cast for a column.
     */
    protected function castValue(string $key, mixed $value): mixed
    {
        if ($value === null || !isset($this->casts[$key])) {
            return $value;
        }

        return match ($this->casts[$key]) {
            'int', 'integer' => (int) $value,
            'float', 'double' => (float) $value,
            'bool', 'boolean' => (bool) $value,
            'json', 'array'   => is_string($value) ? (json_decode($value, true) ?? []) : $value,
            default           => $value,
        };
    }
}

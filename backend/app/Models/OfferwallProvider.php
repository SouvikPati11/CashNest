<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Offerwall provider (DATABASE_DESIGN.md §D.1).
 *
 * The postback secret column is app-encrypted at rest (`_enc`); this accessor
 * returns the usable secret — a KMS/crypto layer can slot in behind it later
 * without changing callers.
 */
final class OfferwallProvider extends BaseModel
{
    protected string $table = 'offerwall_providers';

    /** @var array<int, string> */
    protected array $fillable = [
        'name',
        'slug',
        'api_key_enc',
        'postback_secret_enc',
        'ip_allowlist',
        'currency_ratio',
        'logo_url',
        'is_active',
        'sort_order',
    ];

    /** Secrets/keys never leave the server. */
    protected array $hidden = ['api_key_enc', 'postback_secret_enc'];

    /** @var array<string, string> */
    protected array $casts = [
        'id'           => 'int',
        'is_active'    => 'bool',
        'sort_order'   => 'int',
        'ip_allowlist' => 'json',
    ];

    public function id(): ?int
    {
        $id = $this->get('id');

        return $id === null ? null : (int) $id;
    }

    public function slug(): string
    {
        $slug = $this->get('slug');

        return is_string($slug) ? $slug : '';
    }

    public function postbackSecret(): string
    {
        $secret = $this->get('postback_secret_enc');

        return is_string($secret) ? $secret : '';
    }

    public function currencyRatio(): float
    {
        return (float) $this->get('currency_ratio', 1.0);
    }

    /**
     * @return array<int, string>
     */
    public function ipAllowlist(): array
    {
        $list = $this->get('ip_allowlist');

        if (!is_array($list)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn($v): string => is_string($v) ? $v : '',
            $list
        )));
    }
}

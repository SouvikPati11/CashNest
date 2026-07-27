<?php

declare(strict_types=1);

namespace App\Controllers\Settings;

use App\Contracts\RemoteConfigServiceInterface;
use App\Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Remote config endpoint (API_SPECIFICATION.md §2.77). Public, server-driven.
 */
final class RemoteConfigController extends BaseController
{
    public function __construct(private RemoteConfigServiceInterface $config)
    {
    }

    public function index(Request $request): Response
    {
        $keys = $this->keys($request);

        [$map, $etag] = $this->config->effective($keys);

        return $this->ok($map, 'OK', 200, ['etag' => $etag]);
    }

    /**
     * @return array<int, string>
     */
    private function keys(Request $request): array
    {
        $raw = $request->query('keys');

        if (!is_string($raw) || $raw === '') {
            return [];
        }

        $parts = array_map('trim', explode(',', $raw));

        return array_values(array_filter($parts, static fn(string $k): bool => $k !== ''));
    }
}

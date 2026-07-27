<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\HttpFetcherInterface;

/**
 * cURL-based HTTP GET fetcher.
 *
 * A small, dependency-free outbound GET used for server-to-server calls (Google
 * token verification). TLS verification is always on. Available on virtually all
 * shared hosting where the cURL extension is enabled; falls back to the streams
 * API otherwise.
 */
final class CurlHttpFetcher implements HttpFetcherInterface
{
    public function __construct(private int $timeoutSeconds = 5)
    {
    }

    public function get(string $url): array
    {
        if (function_exists('curl_init')) {
            return $this->curlGet($url);
        }

        return $this->streamGet($url);
    }

    /**
     * @return array{status: int, body: string}
     */
    private function curlGet(string $url): array
    {
        $handle = curl_init($url);

        if ($handle === false) {
            return ['status' => 0, 'body' => ''];
        }

        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_TIMEOUT, $this->timeoutSeconds);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, false);

        $body   = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        return ['status' => $status, 'body' => is_string($body) ? $body : ''];
    }

    /**
     * @return array{status: int, body: string}
     */
    private function streamGet(string $url): array
    {
        $context = stream_context_create([
            'http' => ['timeout' => $this->timeoutSeconds, 'ignore_errors' => true],
            'ssl'  => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);

        $body   = @file_get_contents($url, false, $context);
        $status = 0;

        // $http_response_header is populated by the streams wrapper on request.
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m) === 1) {
            $status = (int) $m[1];
        }

        return ['status' => $status, 'body' => is_string($body) ? $body : ''];
    }
}

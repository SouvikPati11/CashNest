<?php

declare(strict_types=1);

namespace App\Helpers;

use Core\Http\Response;

/**
 * API response helper.
 *
 * Produces the standard response envelope defined in API_SPECIFICATION.md §1.5
 * and §1.6 so every endpoint returns an identical shape:
 * { status, message, data, meta, errors, request_id, timestamp }.
 */
final class ApiResponse
{
    /**
     * A successful response.
     *
     * @param mixed                     $data
     * @param array<string, mixed>|null $meta
     */
    public static function success(
        mixed $data = null,
        string $message = 'OK',
        int $status = 200,
        ?array $meta = null
    ): Response {
        return Response::json(
            self::envelope('success', $message, $data, $meta, null),
            $status
        );
    }

    /**
     * A created (201) response.
     *
     * @param mixed $data
     */
    public static function created(mixed $data = null, string $message = 'Created.'): Response
    {
        return self::success($data, $message, 201);
    }

    /**
     * An accepted (202) response for async/queued work.
     *
     * @param mixed $data
     */
    public static function accepted(mixed $data = null, string $message = 'Accepted.'): Response
    {
        return self::success($data, $message, 202);
    }

    /**
     * An error response.
     *
     * @param array<int, array<string, mixed>> $errors
     */
    public static function error(
        string $message,
        int $status = 400,
        string $code = 'INTERNAL_ERROR',
        array $errors = []
    ): Response {
        if ($errors === []) {
            $errors = [['code' => $code, 'field' => null, 'message' => $message]];
        }

        return Response::json(
            self::envelope('error', $message, null, null, $errors),
            $status
        );
    }

    /**
     * Assemble the envelope with correlation id and timestamp.
     *
     * @param mixed                            $data
     * @param array<string, mixed>|null        $meta
     * @param array<int, array<string, mixed>>|null $errors
     * @return array<string, mixed>
     */
    private static function envelope(
        string $status,
        string $message,
        mixed $data,
        ?array $meta,
        ?array $errors
    ): array {
        return [
            'status'     => $status,
            'message'    => $message,
            'data'       => $data,
            'meta'       => $meta,
            'errors'     => $errors,
            'request_id' => self::requestId(),
            'timestamp'  => gmdate('Y-m-d\TH:i:s\Z'),
        ];
    }

    /**
     * Correlation id: reuse an inbound X-Request-Id when present, else generate.
     */
    private static function requestId(): string
    {
        $incoming = $_SERVER['HTTP_X_REQUEST_ID'] ?? '';

        if (is_string($incoming) && $incoming !== '' && strlen($incoming) <= 128) {
            return preg_replace('/[^A-Za-z0-9\-_.]/', '', $incoming) ?? self::uuid4();
        }

        return self::uuid4();
    }

    /**
     * Generate a RFC-4122 v4 UUID.
     */
    private static function uuid4(): string
    {
        $bytes    = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}

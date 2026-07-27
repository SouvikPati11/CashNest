<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\ApiResponse;
use Core\Http\Response;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
    public function testJsonResponseSetsContentType(): void
    {
        $response = Response::json(['a' => 1], 201);

        self::assertSame(201, $response->status());
        self::assertSame('application/json; charset=utf-8', $response->headers()['Content-Type']);
    }

    public function testWithHeaderIsImmutable(): void
    {
        $original = Response::json([]);
        $modified = $original->withHeader('X-Test', 'yes');

        self::assertArrayNotHasKey('X-Test', $original->headers());
        self::assertSame('yes', $modified->headers()['X-Test']);
    }

    public function testApiResponseEnvelopeShape(): void
    {
        $response = ApiResponse::success(['coins' => 100], 'Loaded');
        $payload  = json_decode($response->body(), true);

        self::assertIsArray($payload);
        self::assertSame('success', $payload['status']);
        self::assertSame('Loaded', $payload['message']);
        self::assertSame(['coins' => 100], $payload['data']);
        self::assertArrayHasKey('request_id', $payload);
        self::assertArrayHasKey('timestamp', $payload);
        self::assertNull($payload['errors']);
    }

    public function testApiResponseErrorEnvelope(): void
    {
        $response = ApiResponse::error('Bad input', 422, 'VALIDATION_ERROR');
        $payload  = json_decode($response->body(), true);

        self::assertSame('error', $payload['status']);
        self::assertSame(422, $response->status());
        self::assertSame('VALIDATION_ERROR', $payload['errors'][0]['code']);
    }
}

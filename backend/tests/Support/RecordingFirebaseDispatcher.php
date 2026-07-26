<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\FirebaseDispatcherInterface;

/**
 * Firebase dispatcher double that records sends and returns a configurable
 * result, so job tests can assert delivered vs failed outcomes without FCM.
 */
final class RecordingFirebaseDispatcher implements FirebaseDispatcherInterface
{
    /** @var array<int, array{token: string, message: array<string, mixed>}> */
    public array $sent = [];

    public function __construct(private bool $result = true)
    {
    }

    public function send(string $token, array $message): bool
    {
        $this->sent[] = ['token' => $token, 'message' => $message];

        return $this->result;
    }
}

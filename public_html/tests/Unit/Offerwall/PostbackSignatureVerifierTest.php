<?php

declare(strict_types=1);

namespace Tests\Unit\Offerwall;

use App\Models\OfferwallProvider;
use App\Services\PostbackSignatureVerifier;
use PHPUnit\Framework\TestCase;

final class PostbackSignatureVerifierTest extends TestCase
{
    private PostbackSignatureVerifier $verifier;

    protected function setUp(): void
    {
        $this->verifier = new PostbackSignatureVerifier();
    }

    private function provider(array $overrides = []): OfferwallProvider
    {
        return OfferwallProvider::fromRow(array_merge([
            'id' => 1, 'slug' => 'p', 'postback_secret_enc' => 'secret', 'ip_allowlist' => null,
        ], $overrides));
    }

    public function testValidSignaturePasses(): void
    {
        $payload = ['transaction_id' => 'T', 'sub_id' => 'S', 'payout' => '50'];
        $payload['signature'] = hash_hmac('sha256', 'T|S|50', 'secret');

        self::assertTrue($this->verifier->verifySignature($this->provider(), $payload));
    }

    public function testTamperedSignatureFails(): void
    {
        self::assertFalse($this->verifier->verifySignature($this->provider(), [
            'transaction_id' => 'T', 'sub_id' => 'S', 'payout' => '50', 'signature' => 'x',
        ]));
    }

    public function testMissingSecretFailsClosed(): void
    {
        $provider = $this->provider(['postback_secret_enc' => '']);
        $payload  = ['transaction_id' => 'T', 'sub_id' => 'S', 'payout' => '50', 'signature' => 'anything'];

        self::assertFalse($this->verifier->verifySignature($provider, $payload));
    }

    public function testEmptyAllowlistAllowsAny(): void
    {
        self::assertTrue($this->verifier->ipAllowed($this->provider(), '8.8.8.8'));
    }

    public function testExactIpMatch(): void
    {
        $provider = $this->provider(['ip_allowlist' => ['1.2.3.4']]);

        self::assertTrue($this->verifier->ipAllowed($provider, '1.2.3.4'));
        self::assertFalse($this->verifier->ipAllowed($provider, '1.2.3.5'));
    }

    public function testCidrMatch(): void
    {
        $provider = $this->provider(['ip_allowlist' => ['10.0.0.0/8']]);

        self::assertTrue($this->verifier->ipAllowed($provider, '10.55.12.9'));
        self::assertFalse($this->verifier->ipAllowed($provider, '11.0.0.1'));
    }
}

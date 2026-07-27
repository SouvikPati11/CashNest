<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Services\EmailVerificationService;
use App\Services\UserService;
use Core\Cache\FileCache;
use Core\Config;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeMailService;
use Tests\Support\InMemoryUserRepository;
use Tests\Support\NullLogger;

final class EmailVerificationServiceTest extends TestCase
{
    private const TOKEN_PREFIX = 'emailverify:token:';
    private const OTP_PREFIX   = 'emailverify:otp:';

    private string $dir;

    private FileCache $cache;

    private InMemoryUserRepository $users;

    private UserService $userService;

    private FakeMailService $mail;

    private EmailVerificationService $service;

    protected function setUp(): void
    {
        $this->dir         = sys_get_temp_dir() . '/cashnest-ev-' . bin2hex(random_bytes(6));
        $this->cache       = new FileCache($this->dir);
        $this->users       = new InMemoryUserRepository();
        $this->userService = new UserService($this->users, new NullLogger());
        $this->mail        = new FakeMailService();

        $this->service = new EmailVerificationService(
            $this->cache,
            $this->mail,
            $this->userService,
            $this->users,
            new Config(['app' => ['url' => 'https://api.test']]),
            new NullLogger()
        );
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->dir);
    }

    public function testSendVerificationEmailsAndStoresOtp(): void
    {
        $user = $this->userService->createUser(['email' => 'a@b.com']);

        $this->service->sendVerification($user);

        self::assertCount(1, $this->mail->sent);
        self::assertSame('a@b.com', $this->mail->sent[0]['to']);
        self::assertTrue($this->cache->has(self::OTP_PREFIX . $user->id()));
    }

    public function testVerifyTokenIsSingleUse(): void
    {
        $this->cache->put(self::TOKEN_PREFIX . 'TOK123', 42, 900);

        self::assertSame(42, $this->service->verifyToken('TOK123'));
        self::assertNull($this->service->verifyToken('TOK123')); // consumed
    }

    public function testVerifyTokenRejectsUnknown(): void
    {
        self::assertNull($this->service->verifyToken('nope'));
    }

    public function testVerifyOtpSuccessIsSingleUse(): void
    {
        $user = $this->userService->createUser(['email' => 'c@d.com']);
        $id   = (int) $user->id();
        $this->cache->put(self::OTP_PREFIX . $id, ['otp' => '654321', 'attempts' => 0], 900);

        self::assertSame($id, $this->service->verifyOtp('C@D.com', '654321'));
        self::assertNull($this->service->verifyOtp('c@d.com', '654321')); // consumed
    }

    public function testVerifyOtpInvalidatesAfterMaxAttempts(): void
    {
        $user = $this->userService->createUser(['email' => 'e@f.com']);
        $id   = (int) $user->id();
        $this->cache->put(self::OTP_PREFIX . $id, ['otp' => '111111', 'attempts' => 0], 900);

        for ($i = 0; $i < 5; $i++) {
            self::assertNull($this->service->verifyOtp('e@f.com', '000000'));
        }

        // Correct code no longer works once the attempt cap invalidated it.
        self::assertNull($this->service->verifyOtp('e@f.com', '111111'));
    }

    public function testMarkVerifiedSetsTimestamp(): void
    {
        $user = $this->userService->createUser(['email' => 'g@h.com']);
        $id   = (int) $user->id();

        $this->service->markVerified($id);

        self::assertNotNull($this->users->rows[$id]['email_verified_at'] ?? null);
    }
}

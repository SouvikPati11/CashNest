<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

use App\Admin\Security\CsrfGuard;
use App\Admin\Security\TwoFactorAuthenticator;
use App\Admin\Services\AdminAuthService;
use App\Admin\Services\AuditLogService;
use App\Helpers\Security;
use PHPUnit\Framework\TestCase;
use Tests\Support\ArraySession;
use Tests\Support\InMemoryAdminAuditLogRepository;
use Tests\Support\InMemoryAdminRepository;
use Tests\Support\TotpGenerator;

final class AdminAuthServiceTest extends TestCase
{
    private const SECRET = 'JBSWY3DPEHPK3PXP';

    private InMemoryAdminRepository $admins;

    private ArraySession $session;

    private InMemoryAdminAuditLogRepository $auditRepo;

    private AdminAuthService $auth;

    protected function setUp(): void
    {
        $this->admins    = new InMemoryAdminRepository();
        $this->session   = new ArraySession();
        $this->auditRepo = new InMemoryAdminAuditLogRepository();

        $this->auth = new AdminAuthService(
            $this->admins,
            new TwoFactorAuthenticator(),
            $this->session,
            new CsrfGuard($this->session),
            new AuditLogService($this->auditRepo)
        );
    }

    private function seedAdmin(bool $twoFactor = false): int
    {
        return $this->admins->seed([
            'email'             => 'admin@cashnest.app',
            'password_hash'     => Security::hashPassword('secret-pass'),
            'two_fa_enabled'    => $twoFactor ? 1 : 0,
            'two_fa_secret_enc' => $twoFactor ? self::SECRET : null,
        ]);
    }

    public function testRejectsWrongPassword(): void
    {
        $this->seedAdmin();

        $result = $this->auth->attempt('admin@cashnest.app', 'nope', null, '1.2.3.4');

        self::assertSame(AdminAuthService::INVALID, $result['status']);
        self::assertNull($this->auth->id());
    }

    public function testLoginWithoutTwoFactorSucceeds(): void
    {
        $id = $this->seedAdmin();

        $result = $this->auth->attempt('admin@cashnest.app', 'secret-pass', null, '9.9.9.9');

        self::assertSame(AdminAuthService::OK, $result['status']);
        self::assertSame($id, $this->auth->id());
        self::assertSame(1, $this->session->regenerated);
        self::assertArrayHasKey($id, $this->admins->logins);
        self::assertSame('admin.login', $this->auditRepo->rows[1]['action']);
    }

    public function testTwoFactorRequiredThenVerified(): void
    {
        $this->seedAdmin(true);

        $pending = $this->auth->attempt('admin@cashnest.app', 'secret-pass', null, '1.1.1.1');
        self::assertSame(AdminAuthService::TWO_FACTOR_REQUIRED, $pending['status']);
        self::assertNull($this->auth->id());

        $ok = $this->auth->attempt('admin@cashnest.app', 'secret-pass', TotpGenerator::now(self::SECRET), '1.1.1.1');
        self::assertSame(AdminAuthService::OK, $ok['status']);
    }

    public function testTwoFactorInvalidCode(): void
    {
        $this->seedAdmin(true);

        $result = $this->auth->attempt('admin@cashnest.app', 'secret-pass', '000000', '1.1.1.1');

        self::assertSame(AdminAuthService::TWO_FACTOR_INVALID, $result['status']);
    }

    public function testLogoutClearsSession(): void
    {
        $this->seedAdmin();
        $this->auth->attempt('admin@cashnest.app', 'secret-pass', null, '9.9.9.9');

        $this->auth->logout('9.9.9.9');

        self::assertNull($this->auth->id());
        self::assertSame('admin.logout', $this->auditRepo->rows[2]['action']);
    }
}

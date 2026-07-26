<?php

declare(strict_types=1);

namespace App\Admin\Services;

use App\Admin\Security\CsrfGuard;
use App\Admin\Security\TwoFactorAuthenticator;
use App\Admin\Support\SessionInterface;
use App\Contracts\AdminRepositoryInterface;
use App\Helpers\Security;
use App\Models\Admin;

/**
 * Admin session authentication.
 *
 * Verifies credentials against the `admins` store, enforces optional TOTP 2FA
 * (2FA-ready — skipped when disabled on the account), and establishes a session
 * with a fresh id + rotated CSRF token to defeat fixation. Every login/logout is
 * audit-logged. Returns a status so the controller can render the right step.
 */
final class AdminAuthService
{
    public const OK                   = 'ok';
    public const INVALID              = 'invalid';
    public const TWO_FACTOR_REQUIRED  = 'two_factor_required';
    public const TWO_FACTOR_INVALID   = 'two_factor_invalid';

    private const SESSION_ADMIN = 'admin_id';

    public function __construct(
        private AdminRepositoryInterface $admins,
        private TwoFactorAuthenticator $twoFactor,
        private SessionInterface $session,
        private CsrfGuard $csrf,
        private AuditLogService $audit
    ) {
    }

    /**
     * Attempt a login. On success the admin id is stored in the session.
     *
     * @return array{status: string, admin_id?: int}
     */
    public function attempt(string $email, string $password, ?string $code, string $ip): array
    {
        $row = $this->admins->findActiveByEmail($email);

        if ($row === null) {
            return ['status' => self::INVALID];
        }

        $admin = Admin::fromRow($row);

        if (!Security::verifyPassword($password, $admin->passwordHash())) {
            return ['status' => self::INVALID];
        }

        if ($admin->twoFactorEnabled()) {
            if ($code === null || $code === '') {
                return ['status' => self::TWO_FACTOR_REQUIRED];
            }

            if (!$this->twoFactor->verify($admin->twoFactorSecret(), $code)) {
                return ['status' => self::TWO_FACTOR_INVALID];
            }
        }

        $adminId = (int) $admin->id();

        $this->session->regenerate();
        $this->session->set(self::SESSION_ADMIN, $adminId);
        $this->csrf->rotate();
        $this->admins->recordLogin($adminId, $ip, gmdate('Y-m-d H:i:s'));
        $this->audit->log($adminId, 'admin.login', ['ip' => $ip]);

        return ['status' => self::OK, 'admin_id' => $adminId];
    }

    public function logout(string $ip): void
    {
        $adminId = $this->id();
        if ($adminId !== null) {
            $this->audit->log($adminId, 'admin.logout', ['ip' => $ip]);
        }

        $this->session->clear();
    }

    /**
     * The authenticated admin id from the session, if any.
     */
    public function id(): ?int
    {
        $id = $this->session->get(self::SESSION_ADMIN);

        return is_int($id) ? $id : (is_numeric($id) ? (int) $id : null);
    }

    /**
     * The authenticated admin row, if logged in.
     *
     * @return array<string, mixed>|null
     */
    public function current(): ?array
    {
        $id = $this->id();

        return $id === null ? null : $this->admins->find($id);
    }
}

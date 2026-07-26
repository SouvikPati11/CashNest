<?php

declare(strict_types=1);

namespace App\Providers;

use App\Admin\Security\CsrfGuard;
use App\Admin\Security\TwoFactorAuthenticator;
use App\Admin\Services\AdminAuthService;
use App\Admin\Services\AuditLogService;
use App\Admin\Services\DashboardService;
use App\Admin\Services\FraudAdminService;
use App\Admin\Services\RbacService;
use App\Admin\Services\ReportService;
use App\Admin\Services\ResourceAdminService;
use App\Admin\Services\UserAdminService;
use App\Admin\Services\WithdrawAdminService;
use App\Admin\Support\AdminResources;
use App\Admin\Support\CsvExporter;
use App\Admin\Support\PhpSession;
use App\Admin\Support\SessionInterface;
use App\Admin\View\AdminView;
use App\Admin\View\ViewRenderer;
use App\Contracts\AdminAuditLogRepositoryInterface;
use App\Contracts\AdminFraudRepositoryInterface;
use App\Contracts\AdminQueryRepositoryInterface;
use App\Contracts\AdminRepositoryInterface;
use App\Contracts\AdminRoleRepositoryInterface;
use App\Contracts\AppSettingRepositoryInterface;
use App\Contracts\UserRepositoryInterface;
use App\Contracts\WithdrawSettlementServiceInterface;
use App\Repositories\AdminAuditLogRepository;
use App\Repositories\AdminFraudRepository;
use App\Repositories\AdminQueryRepository;
use App\Repositories\AdminRepository;
use App\Repositories\AdminRoleRepository;
use Core\Config;
use Core\Contracts\ContainerInterface;
use Core\Database\Database;

/**
 * Admin panel service provider.
 *
 * Wires the admin session/CSRF/2FA security stack, RBAC, audit logging, the view
 * layer, admin repositories, and every management service. Reuses existing
 * bindings (user repository, withdraw settlement, public app settings) without
 * modifying them. Does not modify the kernel — admin routes are registered from
 * routes/admin.php at integration time.
 */
final class AdminServiceProvider
{
    public static function register(ContainerInterface $container): void
    {
        self::bindSecurity($container);
        self::bindRepositories($container);
        self::bindServices($container);
        self::bindView($container);
    }

    private static function bindSecurity(ContainerInterface $container): void
    {
        $container->singleton(
            SessionInterface::class,
            static fn(ContainerInterface $c): PhpSession => new PhpSession(
                (string) $c->get(Config::class)->get('admin.session_key', 'cashnest_admin')
            )
        );

        $container->singleton(
            CsrfGuard::class,
            static fn(ContainerInterface $c): CsrfGuard => new CsrfGuard($c->get(SessionInterface::class))
        );

        $container->singleton(
            TwoFactorAuthenticator::class,
            static fn(ContainerInterface $c): TwoFactorAuthenticator => new TwoFactorAuthenticator()
        );

        $container->singleton(
            CsvExporter::class,
            static fn(ContainerInterface $c): CsvExporter => new CsvExporter()
        );

        $container->singleton(
            AdminResources::class,
            static fn(ContainerInterface $c): AdminResources => new AdminResources()
        );
    }

    private static function bindRepositories(ContainerInterface $container): void
    {
        $container->singleton(
            AdminRepositoryInterface::class,
            static fn(ContainerInterface $c): AdminRepository => new AdminRepository($c->get(Database::class))
        );
        $container->singleton(
            AdminRoleRepositoryInterface::class,
            static fn(ContainerInterface $c): AdminRoleRepository => new AdminRoleRepository($c->get(Database::class))
        );
        $container->singleton(
            AdminAuditLogRepositoryInterface::class,
            static fn(ContainerInterface $c): AdminAuditLogRepository =>
                new AdminAuditLogRepository($c->get(Database::class))
        );
        $container->singleton(
            AdminFraudRepositoryInterface::class,
            static fn(ContainerInterface $c): AdminFraudRepository => new AdminFraudRepository($c->get(Database::class))
        );
        $container->singleton(
            AdminQueryRepositoryInterface::class,
            static fn(ContainerInterface $c): AdminQueryRepository => new AdminQueryRepository($c->get(Database::class))
        );
    }

    private static function bindServices(ContainerInterface $container): void
    {
        $container->singleton(
            RbacService::class,
            static fn(ContainerInterface $c): RbacService =>
                new RbacService($c->get(AdminRoleRepositoryInterface::class))
        );
        $container->singleton(
            AuditLogService::class,
            static fn(ContainerInterface $c): AuditLogService =>
                new AuditLogService($c->get(AdminAuditLogRepositoryInterface::class))
        );
        $container->singleton(
            AdminAuthService::class,
            static fn(ContainerInterface $c): AdminAuthService => new AdminAuthService(
                $c->get(AdminRepositoryInterface::class),
                $c->get(TwoFactorAuthenticator::class),
                $c->get(SessionInterface::class),
                $c->get(CsrfGuard::class),
                $c->get(AuditLogService::class)
            )
        );
        $container->singleton(
            DashboardService::class,
            static fn(ContainerInterface $c): DashboardService => new DashboardService(
                $c->get(AdminQueryRepositoryInterface::class),
                $c->get(AdminFraudRepositoryInterface::class)
            )
        );
        $container->singleton(
            ResourceAdminService::class,
            static fn(ContainerInterface $c): ResourceAdminService => new ResourceAdminService(
                $c->get(AdminResources::class),
                $c->get(AdminQueryRepositoryInterface::class)
            )
        );
        $container->singleton(
            UserAdminService::class,
            static fn(ContainerInterface $c): UserAdminService => new UserAdminService(
                $c->get(AdminQueryRepositoryInterface::class),
                $c->get(UserRepositoryInterface::class),
                $c->get(AuditLogService::class)
            )
        );
        $container->singleton(
            WithdrawAdminService::class,
            static fn(ContainerInterface $c): WithdrawAdminService => new WithdrawAdminService(
                $c->get(AdminQueryRepositoryInterface::class),
                $c->get(WithdrawSettlementServiceInterface::class),
                $c->get(AuditLogService::class)
            )
        );
        $container->singleton(
            FraudAdminService::class,
            static fn(ContainerInterface $c): FraudAdminService => new FraudAdminService(
                $c->get(AdminFraudRepositoryInterface::class),
                $c->get(AuditLogService::class)
            )
        );
        $container->singleton(
            ReportService::class,
            static fn(ContainerInterface $c): ReportService => new ReportService(
                $c->get(AdminQueryRepositoryInterface::class),
                $c->get(CsvExporter::class)
            )
        );
    }

    private static function bindView(ContainerInterface $container): void
    {
        $container->singleton(
            ViewRenderer::class,
            static fn(ContainerInterface $c): ViewRenderer => new ViewRenderer()
        );
        $container->singleton(
            AdminView::class,
            static fn(ContainerInterface $c): AdminView => new AdminView(
                $c->get(ViewRenderer::class),
                $c->get(Config::class),
                $c->get(CsrfGuard::class),
                $c->get(AdminAuthService::class),
                $c->get(RbacService::class),
                $c->get(SessionInterface::class)
            )
        );
    }
}

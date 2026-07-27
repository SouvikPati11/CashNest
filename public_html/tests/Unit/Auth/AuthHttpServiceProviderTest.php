<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Contracts\EmailVerificationServiceInterface;
use App\Contracts\LoginServiceInterface;
use App\Contracts\MailServiceInterface;
use App\Contracts\RegistrationServiceInterface;
use App\Contracts\TransactionRunnerInterface;
use App\Controllers\Auth\EmailVerificationController;
use App\Controllers\Auth\LoginController;
use App\Controllers\Auth\RegisterController;
use App\Providers\AuthHttpServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Services\EmailVerificationService;
use App\Services\LoginService;
use App\Services\RegistrationService;
use Core\Cache\FileCache;
use Core\Config;
use Core\Container;
use Core\Contracts\CacheInterface;
use Core\Contracts\LoggerInterface;
use Core\Database\Database;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeMailService;
use Tests\Support\NullLogger;

final class AuthHttpServiceProviderTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();

        // Foundation-provided bindings the auth module depends on.
        $this->container->instance(Database::class, new Database([]));
        $this->container->instance(LoggerInterface::class, new NullLogger());
        $this->container->instance(CacheInterface::class, new FileCache(sys_get_temp_dir() . '/cashnest-di'));
        $this->container->instance(MailServiceInterface::class, new FakeMailService());
        $this->container->instance(Config::class, new Config(['app' => ['url' => 'https://api.test']]));

        AuthServiceProvider::register($this->container);     // Part 1
        AuthHttpServiceProvider::register($this->container);  // Part 2
    }

    public function testBindsFlowServices(): void
    {
        self::assertInstanceOf(
            RegistrationService::class,
            $this->container->get(RegistrationServiceInterface::class)
        );
        self::assertInstanceOf(
            LoginService::class,
            $this->container->get(LoginServiceInterface::class)
        );
        self::assertInstanceOf(
            EmailVerificationService::class,
            $this->container->get(EmailVerificationServiceInterface::class)
        );
        self::assertInstanceOf(
            TransactionRunnerInterface::class,
            $this->container->get(TransactionRunnerInterface::class)
        );
    }

    public function testControllersAreResolvableByTheRouter(): void
    {
        // The router resolves controllers via the container; autowiring must succeed.
        self::assertInstanceOf(RegisterController::class, $this->container->get(RegisterController::class));
        self::assertInstanceOf(LoginController::class, $this->container->get(LoginController::class));
        self::assertInstanceOf(
            EmailVerificationController::class,
            $this->container->get(EmailVerificationController::class)
        );
    }

    public function testFlowBindingsAreSingletons(): void
    {
        self::assertSame(
            $this->container->get(RegistrationServiceInterface::class),
            $this->container->get(RegistrationServiceInterface::class)
        );
    }
}

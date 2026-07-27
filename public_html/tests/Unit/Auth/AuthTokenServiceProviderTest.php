<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Contracts\AuthTokenServiceInterface;
use App\Contracts\GoogleLoginServiceInterface;
use App\Contracts\GoogleTokenVerifierInterface;
use App\Contracts\HttpFetcherInterface;
use App\Contracts\MailServiceInterface;
use App\Contracts\SessionRepositoryInterface;
use App\Controllers\Auth\GoogleLoginController;
use App\Controllers\Auth\LogoutController;
use App\Controllers\Auth\RefreshTokenController;
use App\Middleware\JwtAuthMiddleware;
use App\Providers\AuthHttpServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\AuthTokenServiceProvider;
use App\Services\AuthTokenService;
use App\Services\GoogleLoginService;
use Core\Cache\FileCache;
use Core\Config;
use Core\Container;
use Core\Contracts\CacheInterface;
use Core\Contracts\LoggerInterface;
use Core\Database\Database;
use Core\Security\JwtService;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeMailService;
use Tests\Support\NullLogger;

final class AuthTokenServiceProviderTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();

        // Foundation-provided bindings.
        $this->container->instance(Database::class, new Database([]));
        $this->container->instance(LoggerInterface::class, new NullLogger());
        $this->container->instance(CacheInterface::class, new FileCache(sys_get_temp_dir() . '/cashnest-di3'));
        $this->container->instance(MailServiceInterface::class, new FakeMailService());
        $this->container->instance(JwtService::class, new JwtService('di-secret-0123456789abcdef', 'HS256'));
        $this->container->instance(Config::class, new Config([
            'jwt'    => ['access_ttl' => 1800, 'refresh_ttl' => 2592000],
            'google' => ['client_id' => 'client-x', 'tokeninfo_url' => 'https://tokeninfo.test'],
        ]));

        AuthServiceProvider::register($this->container);       // Part 1
        AuthHttpServiceProvider::register($this->container);    // Part 2
        AuthTokenServiceProvider::register($this->container);   // Part 3
    }

    public function testBindsTokenAndGoogleServices(): void
    {
        self::assertInstanceOf(
            AuthTokenService::class,
            $this->container->get(AuthTokenServiceInterface::class)
        );
        self::assertInstanceOf(
            GoogleLoginService::class,
            $this->container->get(GoogleLoginServiceInterface::class)
        );
        self::assertInstanceOf(
            GoogleTokenVerifierInterface::class,
            $this->container->get(GoogleTokenVerifierInterface::class)
        );
        self::assertInstanceOf(
            SessionRepositoryInterface::class,
            $this->container->get(SessionRepositoryInterface::class)
        );
        self::assertInstanceOf(
            HttpFetcherInterface::class,
            $this->container->get(HttpFetcherInterface::class)
        );
    }

    public function testControllersAndMiddlewareAreResolvable(): void
    {
        self::assertInstanceOf(GoogleLoginController::class, $this->container->get(GoogleLoginController::class));
        self::assertInstanceOf(RefreshTokenController::class, $this->container->get(RefreshTokenController::class));
        self::assertInstanceOf(LogoutController::class, $this->container->get(LogoutController::class));
        self::assertInstanceOf(JwtAuthMiddleware::class, $this->container->get(JwtAuthMiddleware::class));
    }
}

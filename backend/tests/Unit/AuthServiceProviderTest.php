<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Contracts\AuthenticationRepositoryInterface;
use App\Contracts\AuthenticationServiceInterface;
use App\Contracts\UserRepositoryInterface;
use App\Contracts\UserServiceInterface;
use App\Providers\AuthServiceProvider;
use App\Repositories\AuthenticationRepository;
use App\Repositories\UserRepository;
use App\Services\AuthenticationService;
use App\Services\UserService;
use Core\Container;
use Core\Contracts\LoggerInterface;
use Core\Database\Database;
use PHPUnit\Framework\TestCase;
use Tests\Support\NullLogger;

final class AuthServiceProviderTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();

        // Foundation-provided dependencies the module relies on. Database is
        // never connected during resolution (constructor only stores config).
        $this->container->instance(Database::class, new Database([]));
        $this->container->instance(LoggerInterface::class, new NullLogger());

        AuthServiceProvider::register($this->container);
    }

    public function testBindsRepositoryInterfaces(): void
    {
        self::assertInstanceOf(
            UserRepository::class,
            $this->container->get(UserRepositoryInterface::class)
        );
        self::assertInstanceOf(
            AuthenticationRepository::class,
            $this->container->get(AuthenticationRepositoryInterface::class)
        );
    }

    public function testBindsServiceInterfaces(): void
    {
        self::assertInstanceOf(
            UserService::class,
            $this->container->get(UserServiceInterface::class)
        );
        self::assertInstanceOf(
            AuthenticationService::class,
            $this->container->get(AuthenticationServiceInterface::class)
        );
    }

    public function testBindingsAreSingletons(): void
    {
        self::assertSame(
            $this->container->get(UserServiceInterface::class),
            $this->container->get(UserServiceInterface::class)
        );
    }
}

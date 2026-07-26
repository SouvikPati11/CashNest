<?php

declare(strict_types=1);

namespace Core;

use App\Contracts\FirebaseServiceInterface;
use App\Contracts\MailServiceInterface;
use App\Exceptions\Handler;
use App\Services\FileUploadService;
use App\Services\LogMailService;
use App\Services\NullFirebaseService;
use App\Services\RateLimiter;
use Core\Cache\FileCache;
use Core\Contracts\CacheInterface;
use Core\Contracts\ContainerInterface;
use Core\Contracts\LoggerInterface;
use Core\Contracts\QueueInterface;
use Core\Database\Database;
use Core\Http\Pipeline;
use Core\Http\Request;
use Core\Http\Response;
use Core\Logging\FileLogger;
use Core\Queue\FileQueue;
use Core\Routing\Router;
use Core\Security\JwtService;

/**
 * Application kernel.
 *
 * Bootstraps the framework: loads the environment and configuration, wires the
 * service container, registers routes, and dispatches requests through the global
 * middleware pipeline into the router. Designed to run cleanly on shared hosting.
 */
final class Application
{
    private Container $container;

    private Config $config;

    private Router $router;

    /**
     * Global middleware applied to every request.
     *
     * @var array<int, class-string<\Core\Contracts\MiddlewareInterface>>
     */
    private array $globalMiddleware = [
        \App\Middleware\CorsMiddleware::class,
        \App\Middleware\RateLimitMiddleware::class,
    ];

    private function __construct(private string $basePath)
    {
    }

    /**
     * Boot a fully-configured application instance.
     */
    public static function boot(string $basePath): self
    {
        $app = new self(rtrim($basePath, '/'));

        Env::load($app->basePath . '/.env');

        $app->config = Config::fromDirectory($app->basePath . '/config');

        $app->configureRuntime();
        $app->container = new Container();
        $app->registerCoreBindings();
        $app->registerProviders();
        $app->registerRoutes();

        return $app;
    }

    /**
     * Handle the current request from the SAPI globals and emit the response.
     */
    public function run(): void
    {
        $response = $this->handle(Request::fromGlobals());
        $response->send();
    }

    /**
     * Handle a request object and return a response (testable entry point).
     */
    public function handle(Request $request): Response
    {
        try {
            $destination = fn(Request $req): Response => $this->router->dispatch($req);

            return (new Pipeline($this->container))
                ->through($this->globalMiddleware)
                ->run($request, $destination);
        } catch (\Throwable $e) {
            /** @var Handler $handler */
            $handler = $this->container->get(Handler::class);

            return $handler->render($e);
        }
    }

    /**
     * Expose the container (used by console/cron entry points and tests).
     */
    public function container(): ContainerInterface
    {
        return $this->container;
    }

    public function config(): Config
    {
        return $this->config;
    }

    public function basePath(string $append = ''): string
    {
        return $this->basePath . ($append !== '' ? '/' . ltrim($append, '/') : '');
    }

    /**
     * Configure timezone, error visibility, and error handling for the runtime.
     */
    private function configureRuntime(): void
    {
        date_default_timezone_set((string) $this->config->get('app.timezone', 'UTC'));

        $debug = (bool) $this->config->get('app.debug', false);

        error_reporting(E_ALL);
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');
    }

    /**
     * Register all core service bindings in the container.
     */
    private function registerCoreBindings(): void
    {
        $config   = $this->config;
        $basePath = $this->basePath;

        $this->container->instance(Config::class, $config);
        $this->container->instance(ContainerInterface::class, $this->container);

        // Logger.
        $this->container->singleton(LoggerInterface::class, static function () use ($config, $basePath) {
            return new FileLogger(
                self::resolvePath($basePath, (string) $config->get('logging.path', 'storage/logs')),
                (string) $config->get('logging.level', 'info')
            );
        });

        // Cache.
        $this->container->singleton(CacheInterface::class, static function () use ($config, $basePath) {
            return new FileCache(
                self::resolvePath($basePath, (string) $config->get('cache.path', 'storage/cache'))
            );
        });

        // Queue.
        $this->container->singleton(QueueInterface::class, static function () use ($config, $basePath) {
            return new FileQueue(
                self::resolvePath($basePath, (string) $config->get('queue.path', 'storage/queue'))
            );
        });

        // Database.
        $this->container->singleton(Database::class, static function () use ($config) {
            $default = (string) $config->get('database.default', 'mysql');
            /** @var array<string, mixed> $connection */
            $connection = (array) $config->get('database.connections.' . $default, []);

            return new Database($connection);
        });

        // JWT.
        $this->container->singleton(JwtService::class, static function () use ($config) {
            return new JwtService(
                (string) $config->get('jwt.secret', ''),
                (string) $config->get('jwt.algo', 'HS256'),
                (string) $config->get('jwt.issuer', 'cashnest'),
                (string) $config->get('jwt.audience', 'cashnest-app')
            );
        });

        // Rate limiter.
        $this->container->singleton(RateLimiter::class, function () {
            return new RateLimiter($this->container->get(CacheInterface::class));
        });

        // File uploads.
        $this->container->singleton(FileUploadService::class, static function () use ($config, $basePath) {
            return new FileUploadService(
                self::resolvePath($basePath, (string) $config->get('filesystems.upload_path', 'storage/uploads')),
                (int) $config->get('filesystems.uploads.max_size', 5_242_880),
                (array) $config->get('filesystems.uploads.allowed_mimes', [])
            );
        });

        // Mail (log driver default).
        $this->container->singleton(MailServiceInterface::class, function () {
            return new LogMailService($this->container->get(LoggerInterface::class));
        });

        // Firebase (null driver default).
        $this->container->singleton(FirebaseServiceInterface::class, function () {
            return new NullFirebaseService($this->container->get(LoggerInterface::class));
        });

        // Exception handler.
        $this->container->singleton(Handler::class, function () use ($config) {
            return new Handler(
                $this->container->get(LoggerInterface::class),
                (bool) $config->get('app.debug', false)
            );
        });

        // Router.
        $this->container->singleton(Router::class, function (): Router {
            return new Router($this->container);
        });

        $this->router = $this->container->get(Router::class);
    }

    /**
     * Register every module service provider.
     *
     * Bindings are lazy (closures), so registration order is not significant;
     * each provider resolves its dependencies on first use. Reuses the container
     * built in registerCoreBindings().
     */
    private function registerProviders(): void
    {
        \App\Providers\AuthServiceProvider::register($this->container);
        \App\Providers\AuthHttpServiceProvider::register($this->container);
        \App\Providers\AuthTokenServiceProvider::register($this->container);
        \App\Providers\WalletServiceProvider::register($this->container);
        \App\Providers\RewardsServiceProvider::register($this->container);
        \App\Providers\ReferralServiceProvider::register($this->container);
        \App\Providers\OfferwallServiceProvider::register($this->container);
        \App\Providers\WithdrawServiceProvider::register($this->container);
        \App\Providers\NotificationServiceProvider::register($this->container);
        \App\Providers\SettingsPlatformServiceProvider::register($this->container);
        \App\Providers\AdminServiceProvider::register($this->container);
    }

    /**
     * Load every route definition file into the router.
     *
     * All modules register here: JSON API (auth, wallet, rewards, offerwall,
     * referral, withdraw, notifications, settings platform), the signed postback
     * endpoints, and the session-authenticated admin panel.
     */
    private function registerRoutes(): void
    {
        $router = $this->router;

        $files = [
            'api.php',
            'auth.php',
            'auth_session.php',
            'wallet.php',
            'rewards.php',
            'referral.php',
            'offerwall.php',
            'withdraw.php',
            'notification.php',
            'settings_platform.php',
            'postback.php',
            'admin.php',
        ];

        foreach ($files as $file) {
            $path = $this->basePath . '/routes/' . $file;

            if (is_file($path)) {
                /** @psalm-suppress UnresolvableInclude */
                (require $path)($router);
            }
        }
    }

    /**
     * Resolve a possibly-relative path against the base path.
     */
    private static function resolvePath(string $basePath, string $path): string
    {
        if ($path === '') {
            return $basePath;
        }

        // Absolute path (unix or windows drive) is used as-is.
        if ($path[0] === '/' || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1) {
            return $path;
        }

        return $basePath . '/' . ltrim($path, '/');
    }
}

<?php

declare(strict_types=1);

namespace Core\Http;

/**
 * Immutable-ish HTTP request abstraction.
 *
 * Wraps the raw superglobals into a testable object: method, path, query,
 * headers, parsed body, uploaded files, and a bag of attributes that middleware
 * can populate (e.g. the authenticated user, resolved route params).
 */
final class Request
{
    /** @var array<string, mixed> */
    private array $attributes = [];

    /** @var array<string, string> */
    private array $routeParams = [];

    /**
     * @param string                $method  HTTP verb (uppercase).
     * @param string                $path    URI path without query string.
     * @param array<string, mixed>  $query   Query-string parameters.
     * @param array<string, mixed>  $body    Parsed request body.
     * @param array<string, string> $headers Normalized (lowercase) header map.
     * @param array<string, mixed>  $server  Server params.
     * @param array<string, mixed>  $files   Normalized uploaded files.
     */
    public function __construct(
        private string $method,
        private string $path,
        private array $query = [],
        private array $body = [],
        private array $headers = [],
        private array $server = [],
        private array $files = []
    ) {
    }

    /**
     * Build a Request from PHP superglobals.
     */
    public static function fromGlobals(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        $uri  = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = rawurldecode($path);

        // Strip the front-controller's base directory so routes resolve
        // regardless of where the app is mounted (document root or a
        // subdirectory such as /backend/public on shared hosting). Route
        // definitions and the public API are unchanged.
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $base       = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

        if ($base !== '' && $base !== '/' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }

        $path = '/' . trim($path, '/');

        $headers = self::extractHeaders($_SERVER);
        $body    = self::parseBody($method, $headers);

        return new self(
            $method,
            $path === '//' ? '/' : $path,
            $_GET,
            $body,
            $headers,
            $_SERVER,
            $_FILES
        );
    }

    /**
     * HTTP method (GET, POST, ...).
     */
    public function method(): string
    {
        return $this->method;
    }

    /**
     * URI path, normalized to a leading slash and no trailing slash.
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * A query-string value.
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function allQuery(): array
    {
        return $this->query;
    }

    /**
     * A parsed body value.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    /**
     * The full parsed body.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->body;
    }

    /**
     * Only the given keys from the body (present values).
     *
     * @param array<int, string> $keys
     * @return array<string, mixed>
     */
    public function only(array $keys): array
    {
        return array_intersect_key($this->body, array_flip($keys));
    }

    /**
     * Whether a body key is present (even if null).
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->body);
    }

    /**
     * A request header (case-insensitive).
     */
    public function header(string $name, ?string $default = null): ?string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Extract the Bearer token from the Authorization header, if any.
     */
    public function bearerToken(): ?string
    {
        $header = $this->header('authorization', '');

        if ($header !== null && preg_match('/^Bearer\s+(.+)$/i', $header, $matches) === 1) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Uploaded file entry by input name.
     *
     * @return array<string, mixed>|null
     */
    public function file(string $name): ?array
    {
        return $this->files[$name] ?? null;
    }

    /**
     * Best-effort client IP, honoring common proxy headers.
     */
    public function ip(): string
    {
        $forwarded = $this->header('x-forwarded-for');

        if ($forwarded !== null && $forwarded !== '') {
            $parts = explode(',', $forwarded);
            return trim($parts[0]);
        }

        return (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    /**
     * User-Agent string.
     */
    public function userAgent(): string
    {
        return $this->header('user-agent', '') ?? '';
    }

    /**
     * Store an arbitrary attribute (middleware bag).
     */
    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Read an attribute set earlier in the pipeline.
     */
    public function attribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Assign matched route parameters.
     *
     * @param array<string, string> $params
     */
    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    /**
     * A single route parameter (e.g. {uuid}).
     */
    public function routeParam(string $key, ?string $default = null): ?string
    {
        return $this->routeParams[$key] ?? $default;
    }

    /**
     * @return array<string, string>
     */
    public function routeParams(): array
    {
        return $this->routeParams;
    }

    /**
     * Normalize server headers into a lowercase-keyed map.
     *
     * @param array<string, mixed> $server
     * @return array<string, string>
     */
    private static function extractHeaders(array $server): array
    {
        $headers = [];

        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = (string) $value;
            }
        }

        // Content-Type / Content-Length are not prefixed with HTTP_.
        if (isset($server['CONTENT_TYPE'])) {
            $headers['content-type'] = (string) $server['CONTENT_TYPE'];
        }

        if (isset($server['CONTENT_LENGTH'])) {
            $headers['content-length'] = (string) $server['CONTENT_LENGTH'];
        }

        // Some FastCGI setups expose the auth header via a redirect variable.
        if (!isset($headers['authorization']) && isset($server['REDIRECT_HTTP_AUTHORIZATION'])) {
            $headers['authorization'] = (string) $server['REDIRECT_HTTP_AUTHORIZATION'];
        }

        return $headers;
    }

    /**
     * Parse the request body based on content type.
     *
     * @param array<string, string> $headers
     * @return array<string, mixed>
     */
    private static function parseBody(string $method, array $headers): array
    {
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return [];
        }

        $contentType = strtolower($headers['content-type'] ?? '');

        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');

            if ($raw === false || $raw === '') {
                return [];
            }

            $decoded = json_decode($raw, true);

            return is_array($decoded) ? $decoded : [];
        }

        // Form-encoded or multipart data is already parsed into $_POST.
        return $_POST;
    }
}

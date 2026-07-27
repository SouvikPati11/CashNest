<?php

declare(strict_types=1);

namespace Core\Http;

/**
 * HTTP response value object.
 *
 * Holds a status code, headers, and a (usually JSON) body. Kept transport-only:
 * business code should build responses through the ApiResponse helper so the
 * standard envelope is applied consistently.
 */
final class Response
{
    /** @var array<string, string> */
    private array $headers = [];

    /**
     * @param int    $status  HTTP status code.
     * @param string $body    Raw response body.
     * @param array<string, string> $headers Header map.
     */
    public function __construct(
        private int $status = 200,
        private string $body = '',
        array $headers = []
    ) {
        foreach ($headers as $name => $value) {
            $this->headers[$this->normalizeHeaderName($name)] = $value;
        }
    }

    /**
     * Build a JSON response, encoding the payload.
     *
     * @param array<string, mixed>|array<int, mixed> $payload
     * @param array<string, string>                  $headers
     */
    public static function json(array $payload, int $status = 200, array $headers = []): self
    {
        $body = json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        if ($body === false) {
            $body   = '{"status":"error","message":"Response encoding failed."}';
            $status = 500;
        }

        $headers['Content-Type'] = 'application/json; charset=utf-8';

        return new self($status, $body, $headers);
    }

    /**
     * Build an empty (204) response.
     */
    public static function noContent(int $status = 204): self
    {
        return new self($status, '');
    }

    /**
     * Return a copy with an additional/overwritten header.
     */
    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$this->normalizeHeaderName($name)] = $value;

        return $clone;
    }

    /**
     * Return a copy with a different status code.
     */
    public function withStatus(int $status): self
    {
        $clone = clone $this;
        $clone->status = $status;

        return $clone;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function body(): string
    {
        return $this->body;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Emit the response to the client (headers + body).
     *
     * Guards against output when headers are already sent (e.g. in tests the
     * caller should read status()/body() instead of calling send()).
     */
    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);

            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value, true);
            }
        }

        echo $this->body;
    }

    /**
     * Normalize a header name to Canonical-Case.
     */
    private function normalizeHeaderName(string $name): string
    {
        $name = str_replace('_', '-', strtolower(trim($name)));

        return implode('-', array_map('ucfirst', explode('-', $name)));
    }
}

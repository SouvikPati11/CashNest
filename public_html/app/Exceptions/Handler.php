<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Helpers\ApiResponse;
use Core\Contracts\LoggerInterface;
use Core\Exceptions\TokenException;
use Core\Http\Response;

/**
 * Global exception handler.
 *
 * Converts any throwable into the standard error envelope. Known HttpExceptions
 * map directly to their status/code; framework token errors map to the auth
 * error codes; everything else becomes a 500 with details hidden unless debug is
 * enabled. Every server-side error is logged with its request id.
 */
final class Handler
{
    public function __construct(
        private LoggerInterface $logger,
        private bool $debug = false
    ) {
    }

    /**
     * Render a throwable into an HTTP response.
     */
    public function render(\Throwable $e): Response
    {
        if ($e instanceof HttpException) {
            return $this->renderHttpException($e);
        }

        if ($e instanceof TokenException) {
            return $this->renderTokenException($e);
        }

        return $this->renderUnexpected($e);
    }

    private function renderHttpException(HttpException $e): Response
    {
        // Log server-side (5xx) HTTP exceptions; client errors are expected noise.
        if ($e->getStatusCode() >= 500) {
            $this->logger->error($e->getMessage(), ['exception' => $e::class]);
        }

        $response = ApiResponse::error(
            $e->getMessage(),
            $e->getStatusCode(),
            $e->getErrorCode(),
            $e->getErrors()
        );

        if ($e instanceof TooManyRequestsException) {
            $response = $response->withHeader('Retry-After', (string) $e->getRetryAfter());
        }

        if ($e instanceof MethodNotAllowedException && $e->getAllowedMethods() !== []) {
            $response = $response->withHeader('Allow', implode(', ', $e->getAllowedMethods()));
        }

        return $response;
    }

    private function renderTokenException(TokenException $e): Response
    {
        $expired = $e->reason() === TokenException::REASON_EXPIRED;

        return ApiResponse::error(
            $expired ? 'Access token has expired.' : 'Access token is invalid.',
            401,
            $expired ? 'TOKEN_EXPIRED' : 'INVALID_TOKEN'
        );
    }

    private function renderUnexpected(\Throwable $e): Response
    {
        $this->logger->critical('Unhandled exception.', [
            'exception' => $e::class,
            'message'   => $e->getMessage(),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
        ]);

        $message = $this->debug
            ? $e->getMessage()
            : 'An unexpected error occurred. Please try again later.';

        $errors = [];

        if ($this->debug) {
            $errors = [[
                'code'    => 'INTERNAL_ERROR',
                'field'   => null,
                'message' => $e->getMessage(),
                'trace'   => explode("\n", $e->getTraceAsString()),
            ]];
        }

        return ApiResponse::error($message, 500, 'INTERNAL_ERROR', $errors);
    }
}

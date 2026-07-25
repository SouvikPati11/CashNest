<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\ApiResponse;
use App\Validation\Validator;
use Core\Http\Response;

/**
 * Base controller.
 *
 * Provides response and validation conveniences shared by every controller.
 * Controllers stay thin: parse input, delegate to services, and shape the
 * response. No business logic lives here.
 */
abstract class BaseController
{
    /**
     * Standard success response.
     *
     * @param mixed                     $data
     * @param array<string, mixed>|null $meta
     */
    protected function ok(
        mixed $data = null,
        string $message = 'OK',
        int $status = 200,
        ?array $meta = null
    ): Response {
        return ApiResponse::success($data, $message, $status, $meta);
    }

    /**
     * Standard created response.
     *
     * @param mixed $data
     */
    protected function created(mixed $data = null, string $message = 'Created.'): Response
    {
        return ApiResponse::created($data, $message);
    }

    /**
     * Standard accepted response.
     *
     * @param mixed $data
     */
    protected function accepted(mixed $data = null, string $message = 'Accepted.'): Response
    {
        return ApiResponse::accepted($data, $message);
    }

    /**
     * Validate an input array, throwing ValidationException on failure.
     *
     * @param array<string, mixed>  $data
     * @param array<string, string> $rules
     * @param array<string, string> $messages
     * @return array<string, mixed> The validated fields.
     */
    protected function validate(array $data, array $rules, array $messages = []): array
    {
        $validator = new Validator($data, $rules, $messages);
        $validator->validateOrFail();

        return $validator->validated();
    }
}

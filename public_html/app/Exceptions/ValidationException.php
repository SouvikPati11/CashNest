<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Thrown when request input fails validation (HTTP 422).
 */
final class ValidationException extends HttpException
{
    /**
     * @param array<string, array<int, string>> $failures Map of field => messages.
     */
    public function __construct(array $failures, string $message = 'Validation failed.')
    {
        $errors = [];

        foreach ($failures as $field => $messages) {
            foreach ($messages as $singleMessage) {
                $errors[] = [
                    'code'    => 'VALIDATION_ERROR',
                    'field'   => is_string($field) ? $field : null,
                    'message' => $singleMessage,
                ];
            }
        }

        parent::__construct(422, 'VALIDATION_ERROR', $message, $errors);
    }
}

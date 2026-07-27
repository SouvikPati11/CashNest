<?php

declare(strict_types=1);

namespace Core\Exceptions;

/**
 * Thrown when a database connection or query fails.
 *
 * Wraps the underlying PDOException so callers never leak raw driver details to
 * the client; the original is preserved as `getPrevious()` for logging.
 */
final class DatabaseException extends \RuntimeException
{
}

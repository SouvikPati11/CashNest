/// Sealed hierarchy of application-level failures.
///
/// All lower layers (network, storage) translate raw errors into one of these
/// so the UI and error mapper reason about a small, exhaustive set of cases.
sealed class AppException implements Exception {
  const AppException(this.message, {this.cause, this.stackTrace});

  final String message;
  final Object? cause;
  final StackTrace? stackTrace;

  @override
  String toString() => '$runtimeType: $message';
}

/// No connectivity / request never reached the server.
class NetworkException extends AppException {
  const NetworkException(super.message, {super.cause, super.stackTrace});
}

/// Request timed out.
class TimeoutException extends AppException {
  const TimeoutException(super.message, {super.cause, super.stackTrace});
}

/// Server returned a non-2xx response.
class ServerException extends AppException {
  const ServerException(
    super.message, {
    required this.statusCode,
    this.errorCode,
    super.cause,
    super.stackTrace,
  });

  final int statusCode;
  final String? errorCode;
}

/// Authentication is required or the session expired (HTTP 401).
class UnauthorizedException extends AppException {
  const UnauthorizedException(super.message, {super.cause, super.stackTrace});
}

/// Input validation failed (HTTP 422) with optional field errors.
class ValidationException extends AppException {
  const ValidationException(
    super.message, {
    this.fieldErrors = const {},
    super.cause,
    super.stackTrace,
  });

  final Map<String, List<String>> fieldErrors;
}

/// Local cache/storage failure.
class CacheException extends AppException {
  const CacheException(super.message, {super.cause, super.stackTrace});
}

/// Anything not otherwise classified.
class UnknownException extends AppException {
  const UnknownException(super.message, {super.cause, super.stackTrace});
}

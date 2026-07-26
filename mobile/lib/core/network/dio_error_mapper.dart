import 'package:dio/dio.dart';

import '../error/app_exception.dart';

/// Translates low-level [DioException]s into the app's [AppException] hierarchy.
///
/// Also understands the backend's standard error envelope
/// (`{ status, message, errors:[{code, field, message}] }`) so server messages
/// and field-level validation errors surface to the UI.
abstract final class DioErrorMapper {
  const DioErrorMapper._();

  static AppException map(DioException exception) {
    final stack = exception.stackTrace;

    switch (exception.type) {
      case DioExceptionType.connectionTimeout:
      case DioExceptionType.sendTimeout:
      case DioExceptionType.receiveTimeout:
        return TimeoutException('The request timed out.', cause: exception, stackTrace: stack);

      case DioExceptionType.connectionError:
        return NetworkException('No internet connection.', cause: exception, stackTrace: stack);

      case DioExceptionType.badCertificate:
        return NetworkException('Insecure connection.', cause: exception, stackTrace: stack);

      case DioExceptionType.cancel:
        return UnknownException('The request was cancelled.', cause: exception, stackTrace: stack);

      case DioExceptionType.badResponse:
        return _mapResponse(exception);

      case DioExceptionType.unknown:
        return NetworkException('Something went wrong.', cause: exception, stackTrace: stack);
    }
  }

  static AppException _mapResponse(DioException exception) {
    final response = exception.response;
    final status = response?.statusCode ?? 0;
    final data = response?.data;

    final message = _extractMessage(data) ?? 'Request failed ($status).';
    final errorCode = _extractErrorCode(data);

    if (status == 401) {
      return UnauthorizedException(message, cause: exception, stackTrace: exception.stackTrace);
    }
    if (status == 422) {
      return ValidationException(
        message,
        fieldErrors: _extractFieldErrors(data),
        cause: exception,
        stackTrace: exception.stackTrace,
      );
    }

    return ServerException(
      message,
      statusCode: status,
      errorCode: errorCode,
      cause: exception,
      stackTrace: exception.stackTrace,
    );
  }

  static String? _extractMessage(Object? data) {
    if (data is Map && data['message'] is String) {
      return data['message'] as String;
    }
    return null;
  }

  static String? _extractErrorCode(Object? data) {
    if (data is Map) {
      final errors = data['errors'];
      if (errors is List && errors.isNotEmpty && errors.first is Map) {
        final code = (errors.first as Map)['code'];
        if (code is String) {
          return code;
        }
      }
    }
    return null;
  }

  static Map<String, List<String>> _extractFieldErrors(Object? data) {
    final result = <String, List<String>>{};
    if (data is Map && data['errors'] is List) {
      for (final entry in data['errors'] as List) {
        if (entry is Map && entry['field'] is String && entry['message'] is String) {
          final field = entry['field'] as String;
          result.putIfAbsent(field, () => <String>[]).add(entry['message'] as String);
        }
      }
    }
    return result;
  }
}

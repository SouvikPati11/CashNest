import 'package:dio/dio.dart';

import '../../logging/app_logger.dart';

/// Retries transient failures (timeouts / connection errors) for safe,
/// idempotent requests with exponential backoff.
///
/// Only GET requests are retried by default; other methods may be opted in via
/// `extra['retryable'] = true`. A per-request `retryCount` guards the ceiling.
class RetryInterceptor extends Interceptor {
  RetryInterceptor(this._dio, this._logger, {this.maxRetries = 2});

  final Dio _dio;
  final AppLogger _logger;
  final int maxRetries;

  static const List<Duration> _backoff = [
    Duration(milliseconds: 400),
    Duration(milliseconds: 900),
    Duration(seconds: 2),
  ];

  @override
  Future<void> onError(DioException err, ErrorInterceptorHandler handler) async {
    final options = err.requestOptions;
    final attempt = (options.extra['retryCount'] as int?) ?? 0;

    if (!_shouldRetry(err, options, attempt)) {
      handler.next(err);
      return;
    }

    final delay = _backoff[attempt.clamp(0, _backoff.length - 1)];
    await Future<void>.delayed(delay);

    options.extra['retryCount'] = attempt + 1;
    _logger.debug('Retrying ${options.method} ${options.uri} (attempt ${attempt + 1})');

    try {
      final response = await _dio.fetch<dynamic>(options);
      handler.resolve(response);
    } on DioException catch (error) {
      handler.next(error);
    }
  }

  bool _shouldRetry(DioException err, RequestOptions options, int attempt) {
    if (attempt >= maxRetries) {
      return false;
    }

    final retryable = options.extra['retryable'] as bool? ?? (options.method.toUpperCase() == 'GET');
    if (!retryable) {
      return false;
    }

    return err.type == DioExceptionType.connectionTimeout ||
        err.type == DioExceptionType.receiveTimeout ||
        err.type == DioExceptionType.sendTimeout ||
        err.type == DioExceptionType.connectionError;
  }
}

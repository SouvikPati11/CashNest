import 'package:dio/dio.dart';

import '../../logging/app_logger.dart';

/// Logs outgoing requests, responses, and errors. Enabled only when network
/// logging is on (non-production) to avoid leaking data in release builds.
class LoggingInterceptor extends Interceptor {
  LoggingInterceptor(this._logger, {required this.enabled});

  final AppLogger _logger;
  final bool enabled;

  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) {
    if (enabled) {
      _logger.debug('→ ${options.method} ${options.uri}');
    }
    handler.next(options);
  }

  @override
  void onResponse(Response<dynamic> response, ResponseInterceptorHandler handler) {
    if (enabled) {
      _logger.debug('← ${response.statusCode} ${response.requestOptions.uri}');
    }
    handler.next(response);
  }

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) {
    if (enabled) {
      _logger.warn('✗ ${err.response?.statusCode ?? '-'} ${err.requestOptions.uri}: ${err.message}');
    }
    handler.next(err);
  }
}

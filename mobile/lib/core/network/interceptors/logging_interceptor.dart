import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';

import '../../logging/app_logger.dart';

/// Logs outgoing requests, responses, and errors.
///
/// Uses [debugPrint] (visible via `flutter logs` / logcat, even in release)
/// because the app logger suppresses low-severity lines in production. Request
/// bodies are intentionally NOT printed (they may contain passwords); the full
/// request URL, response status, and response/error body ARE printed so failures
/// can be diagnosed — including the server's actual error message.
class LoggingInterceptor extends Interceptor {
  LoggingInterceptor(this._logger, {required this.enabled});

  final AppLogger _logger;
  final bool enabled;

  static const int _maxBody = 2000;

  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) {
    if (enabled) {
      debugPrint('[HTTP] → ${options.method} ${options.uri}');
    }
    handler.next(options);
  }

  @override
  void onResponse(Response<dynamic> response, ResponseInterceptorHandler handler) {
    if (enabled) {
      debugPrint(
        '[HTTP] ← ${response.statusCode} ${response.requestOptions.uri}\n'
        '[HTTP]   body: ${_truncate(response.data)}',
      );
    }
    handler.next(response);
  }

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) {
    if (enabled) {
      final status = err.response?.statusCode?.toString() ?? '-';
      final body = err.response?.data != null ? _truncate(err.response?.data) : err.message;
      debugPrint(
        '[HTTP] ✗ $status ${err.requestOptions.uri}\n'
        '[HTTP]   type: ${err.type}\n'
        '[HTTP]   body: $body',
      );
    }
    // Also record a warning through the app logger (survives in production).
    _logger.warn('HTTP error ${err.response?.statusCode ?? '-'} ${err.requestOptions.uri}');
    handler.next(err);
  }

  String _truncate(Object? value) {
    final text = value?.toString() ?? '';
    return text.length <= _maxBody ? text : '${text.substring(0, _maxBody)}…(truncated)';
  }
}

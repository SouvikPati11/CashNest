import 'package:dio/dio.dart';

import '../../logging/app_logger.dart';
import '../../storage/secure_storage_service.dart';

/// Refresh-token flow placeholder.
///
/// On a 401 this is where a real implementation would call the refresh endpoint,
/// persist the new tokens, and replay the failed request (serialising concurrent
/// refreshes behind a single in-flight future). The auth feature is out of scope
/// for the foundation, so this hook currently clears the stored tokens and lets
/// the error propagate — the router's auth guard then routes to sign-in.
///
/// The seam is intentionally isolated so wiring the real endpoint later touches
/// only this file.
class RefreshTokenInterceptor extends Interceptor {
  RefreshTokenInterceptor(this._storage, this._logger);

  final SecureStorageService _storage;
  final AppLogger _logger;

  @override
  Future<void> onError(DioException err, ErrorInterceptorHandler handler) async {
    final isUnauthorized = err.response?.statusCode == 401;
    final alreadyRetried = err.requestOptions.extra['refreshRetried'] == true;

    if (!isUnauthorized || alreadyRetried) {
      handler.next(err);
      return;
    }

    // TODO(auth): call POST /auth/refresh, persist new tokens, and replay the
    // request. Until the auth feature lands, drop the session and propagate.
    _logger.warn('Access token rejected (401); clearing session.');
    await _storage.clearTokens();

    handler.next(err);
  }
}

import 'package:dio/dio.dart';

import '../../logging/app_logger.dart';
import '../../storage/secure_storage_service.dart';

/// Refreshes an expired access token on a 401 and replays the failed request,
/// so a persisted session survives access-token expiry (and app restarts).
///
/// On a 401 it calls `POST {baseUrl}/auth/refresh` with the stored refresh
/// token, persists the returned pair, and replays the original request with the
/// new access token. Concurrent 401s share a single in-flight refresh. If the
/// refresh token is missing/expired or refresh fails, the tokens are cleared and
/// the error propagates so the router's guard routes to sign-in.
///
/// The refresh call and the replay use a bare [Dio] (no interceptors) to avoid
/// recursion; the replay is marked so a second 401 does not loop.
class RefreshTokenInterceptor extends Interceptor {
  RefreshTokenInterceptor(this._storage, this._logger);

  final SecureStorageService _storage;
  final AppLogger _logger;

  static const String _retriedKey = 'refreshRetried';
  static const String _refreshCallKey = 'isRefreshCall';

  Future<bool>? _inFlight;

  @override
  Future<void> onError(DioException err, ErrorInterceptorHandler handler) async {
    final options = err.requestOptions;
    final isUnauthorized = err.response?.statusCode == 401;
    final alreadyRetried = options.extra[_retriedKey] == true;
    final isRefreshCall = options.extra[_refreshCallKey] == true;

    if (!isUnauthorized || alreadyRetried || isRefreshCall) {
      handler.next(err);
      return;
    }

    // Serialise concurrent refreshes behind a single in-flight future.
    final refreshed = await (_inFlight ??= _refresh(options.baseUrl));
    _inFlight = null;

    if (!refreshed) {
      await _storage.clearTokens();
      handler.next(err);
      return;
    }

    try {
      final token = await _storage.readAccessToken();
      options.extra[_retriedKey] = true;
      if (token != null && token.isNotEmpty) {
        options.headers['Authorization'] = 'Bearer $token';
      }
      final response = await Dio(BaseOptions(baseUrl: options.baseUrl)).fetch<dynamic>(options);
      handler.resolve(response);
    } catch (_) {
      handler.next(err);
    }
  }

  /// Exchanges the stored refresh token for a new pair; returns whether it
  /// succeeded (and persisted the new tokens).
  Future<bool> _refresh(String baseUrl) async {
    try {
      final refreshToken = await _storage.readRefreshToken();
      if (refreshToken == null || refreshToken.isEmpty) {
        return false;
      }

      final dio = Dio(BaseOptions(
        baseUrl: baseUrl,
        headers: const {'Accept': 'application/json', 'Content-Type': 'application/json'},
      ));
      final res = await dio.post<dynamic>(
        '/auth/refresh',
        data: {'refresh_token': refreshToken},
        options: Options(extra: <String, dynamic>{_refreshCallKey: true}),
      );

      final data = res.data;
      final tokens = (data is Map && data['data'] is Map) ? (data['data'] as Map)['tokens'] : null;
      if (tokens is Map) {
        final access = tokens['access_token'] as String? ?? '';
        final refresh = tokens['refresh_token'] as String? ?? refreshToken;
        if (access.isNotEmpty) {
          await _storage.saveTokens(accessToken: access, refreshToken: refresh);
          return true;
        }
      }
      return false;
    } catch (error) {
      _logger.warn('Token refresh failed: $error');
      return false;
    }
  }
}

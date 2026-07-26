import 'package:dio/dio.dart';

import '../../storage/secure_storage_service.dart';

/// Attaches the bearer access token to outgoing requests.
///
/// Requests may opt out (e.g. auth endpoints) by setting
/// `extra['requiresAuth'] = false` on the [RequestOptions].
class JwtInterceptor extends Interceptor {
  JwtInterceptor(this._storage);

  final SecureStorageService _storage;

  static const String requiresAuthKey = 'requiresAuth';

  @override
  Future<void> onRequest(RequestOptions options, RequestInterceptorHandler handler) async {
    final requiresAuth = options.extra[requiresAuthKey] as bool? ?? true;

    if (requiresAuth) {
      final token = await _storage.readAccessToken();
      if (token != null && token.isNotEmpty) {
        options.headers['Authorization'] = 'Bearer $token';
      }
    }

    handler.next(options);
  }
}

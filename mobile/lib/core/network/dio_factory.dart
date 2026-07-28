import 'package:dio/dio.dart';

import '../config/app_config.dart';
import '../connectivity/connectivity_service.dart';
import '../logging/app_logger.dart';
import '../storage/secure_storage_service.dart';
import 'interceptors/jwt_interceptor.dart';
import 'interceptors/logging_interceptor.dart';
import 'interceptors/refresh_token_interceptor.dart';
import 'interceptors/retry_interceptor.dart';

/// Builds a fully-configured [Dio] with the app's interceptor chain.
///
/// Interceptor order matters: JWT (attach token) → retry (transient errors) →
/// refresh (401 handling) → logging (observe last).
///
/// A request-blocking connectivity pre-check was intentionally removed: it used
/// `connectivity_plus.checkConnectivity()`, which reports whether a network
/// interface exists — NOT whether the server is reachable — and could reject a
/// perfectly valid request with a fabricated "No internet connection" before it
/// was ever sent, masking real server responses (404/401/422/500). The offline
/// banner still observes connectivity separately. Real failures now surface with
/// their true cause and the server's message.
Dio createDio({
  required AppConfig config,
  required SecureStorageService storage,
  required ConnectivityService connectivity,
  required AppLogger logger,
}) {
  final dio = Dio(
    BaseOptions(
      baseUrl: config.apiBaseUrl,
      connectTimeout: config.connectTimeout,
      receiveTimeout: config.receiveTimeout,
      headers: const {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      responseType: ResponseType.json,
    ),
  );

  dio.interceptors.addAll([
    JwtInterceptor(storage),
    RetryInterceptor(dio, logger),
    RefreshTokenInterceptor(storage, logger),
    // Always enabled so the request URL, response status, and response/error
    // body are visible in logs (flutter logs / logcat) for diagnosis.
    LoggingInterceptor(logger, enabled: true),
  ]);

  return dio;
}

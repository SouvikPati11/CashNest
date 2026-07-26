import 'package:dio/dio.dart';

import '../config/app_config.dart';
import '../connectivity/connectivity_service.dart';
import '../logging/app_logger.dart';
import '../storage/secure_storage_service.dart';
import 'interceptors/connectivity_interceptor.dart';
import 'interceptors/jwt_interceptor.dart';
import 'interceptors/logging_interceptor.dart';
import 'interceptors/refresh_token_interceptor.dart';
import 'interceptors/retry_interceptor.dart';

/// Builds a fully-configured [Dio] with the app's interceptor chain.
///
/// Interceptor order matters: connectivity (fail fast) → JWT (attach token) →
/// retry (transient errors) → refresh (401 handling) → logging (observe last).
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
    ConnectivityInterceptor(connectivity),
    JwtInterceptor(storage),
    RetryInterceptor(dio, logger),
    RefreshTokenInterceptor(storage, logger),
    LoggingInterceptor(logger, enabled: config.enableNetworkLogging),
  ]);

  return dio;
}

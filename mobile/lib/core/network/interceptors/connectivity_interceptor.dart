import 'package:dio/dio.dart';

import '../../connectivity/connectivity_service.dart';

/// Fails fast when the device is offline, avoiding a doomed socket attempt and
/// surfacing a clean connectivity error to the mapper.
class ConnectivityInterceptor extends Interceptor {
  ConnectivityInterceptor(this._connectivity);

  final ConnectivityService _connectivity;

  @override
  Future<void> onRequest(RequestOptions options, RequestInterceptorHandler handler) async {
    final online = await _connectivity.isOnline();

    if (!online) {
      handler.reject(
        DioException.connectionError(
          requestOptions: options,
          reason: 'No internet connection.',
        ),
      );
      return;
    }

    handler.next(options);
  }
}

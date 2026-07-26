import 'package:dio/dio.dart';

import '../error/app_exception.dart';
import 'api_result.dart';
import 'dio_error_mapper.dart';

/// High-level HTTP client returning [ApiResult]s.
///
/// Wraps a configured [Dio] instance and converts responses/errors into the
/// app's typed result + exception model. Feature repositories depend on this
/// rather than Dio directly.
class ApiClient {
  ApiClient(this._dio);

  final Dio _dio;

  /// The underlying Dio (exposed for advanced cases such as downloads).
  Dio get raw => _dio;

  Future<ApiResult<T>> get<T>(
    String path, {
    Map<String, dynamic>? query,
    T Function(dynamic data)? decoder,
    Options? options,
  }) {
    return _send<T>(
      () => _dio.get<dynamic>(path, queryParameters: query, options: options),
      decoder,
    );
  }

  Future<ApiResult<T>> post<T>(
    String path, {
    Object? body,
    Map<String, dynamic>? query,
    T Function(dynamic data)? decoder,
    Options? options,
  }) {
    return _send<T>(
      () => _dio.post<dynamic>(path, data: body, queryParameters: query, options: options),
      decoder,
    );
  }

  Future<ApiResult<T>> put<T>(
    String path, {
    Object? body,
    T Function(dynamic data)? decoder,
    Options? options,
  }) {
    return _send<T>(
      () => _dio.put<dynamic>(path, data: body, options: options),
      decoder,
    );
  }

  Future<ApiResult<T>> delete<T>(
    String path, {
    Object? body,
    T Function(dynamic data)? decoder,
    Options? options,
  }) {
    return _send<T>(
      () => _dio.delete<dynamic>(path, data: body, options: options),
      decoder,
    );
  }

  Future<ApiResult<T>> _send<T>(
    Future<Response<dynamic>> Function() request,
    T Function(dynamic data)? decoder,
  ) async {
    try {
      final response = await request();
      final payload = _unwrap(response.data);
      final data = decoder != null ? decoder(payload) : payload as T;
      return ApiResult<T>.success(data);
    } on DioException catch (error) {
      return ApiResult<T>.failure(DioErrorMapper.map(error));
    } catch (error, stack) {
      return ApiResult<T>.failure(
        UnknownException('Unexpected error.', cause: error, stackTrace: stack),
      );
    }
  }

  /// Unwrap the backend envelope (`{ status, data, ... }`) to the `data` field
  /// when present; otherwise return the raw payload.
  dynamic _unwrap(dynamic data) {
    if (data is Map && data.containsKey('data')) {
      return data['data'];
    }
    return data;
  }
}

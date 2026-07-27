import 'package:dio/dio.dart';

import '../../../core/error/app_exception.dart';
import '../../../core/network/api_client.dart';
import '../../../core/network/api_result.dart';
import '../../../core/network/dio_error_mapper.dart';
import '../../offerwall/models/page_result.dart';
import '../models/cancel_result.dart';
import '../models/conversion_info.dart';
import '../models/withdraw_detail.dart';
import '../models/withdraw_method.dart';
import '../models/withdraw_request.dart';
import '../services/idempotency_key.dart';
import 'withdraw_repository.dart';

/// [WithdrawRepository] over the foundation [ApiClient]. The request mutation
/// sends the required `X-Idempotency-Key`; the paginated history reads the full
/// envelope via [ApiClient.raw] for `meta.pagination` and reuses
/// [DioErrorMapper]. The foundation is not modified.
class WithdrawRepositoryImpl implements WithdrawRepository {
  WithdrawRepositoryImpl(this._client);

  final ApiClient _client;

  static Map<String, dynamic> _map(dynamic data) =>
      data is Map<String, dynamic> ? data : const <String, dynamic>{};

  static List<T> _list<T>(dynamic data, T Function(Map<String, dynamic>) fromJson) {
    if (data is! List) {
      return <T>[];
    }
    return data.whereType<Map<String, dynamic>>().map(fromJson).toList(growable: false);
  }

  @override
  Future<ApiResult<List<WithdrawMethod>>> fetchMethods() {
    return _client.get<List<WithdrawMethod>>(
      '/withdraw/methods',
      decoder: (data) => _list(data, WithdrawMethod.fromJson),
    );
  }

  @override
  Future<ApiResult<ConversionInfo>> fetchConversion() {
    return _client.get<ConversionInfo>(
      '/wallet/conversion',
      decoder: (data) => ConversionInfo.fromJson(_map(data)),
    );
  }

  @override
  Future<ApiResult<WithdrawRequest>> createRequest({
    required String methodCode,
    required int coinsAmount,
    required Map<String, dynamic> paymentDetail,
  }) {
    return _client.post<WithdrawRequest>(
      '/withdraw/request',
      body: {
        'method_code': methodCode,
        'coins_amount': coinsAmount,
        'payment_detail': paymentDetail,
      },
      options: Options(headers: {'X-Idempotency-Key': IdempotencyKey.generate()}),
      decoder: (data) => WithdrawRequest.fromJson(_map(data)),
    );
  }

  @override
  Future<ApiResult<PageResult<WithdrawRequest>>> fetchHistory({
    String? status,
    String? cursor,
    int limit = 20,
  }) async {
    final query = <String, dynamic>{
      'limit': limit,
      if (cursor != null) 'cursor': cursor,
      if (status != null) 'filter[status]': status,
    };
    try {
      final response = await _client.raw.get<dynamic>('/withdraw/history', queryParameters: query);
      final envelope = _map(response.data);
      final items = _list(envelope['data'], WithdrawRequest.fromJson);
      final pagination = _map(_map(envelope['meta'])['pagination']);
      final nextCursor = pagination['next_cursor'] as String?;
      final hasMore = pagination['has_more'] as bool? ?? (nextCursor != null);
      return ApiResult<PageResult<WithdrawRequest>>.success(
        PageResult<WithdrawRequest>(items: items, nextCursor: nextCursor, hasMore: hasMore),
      );
    } on DioException catch (error) {
      return ApiResult<PageResult<WithdrawRequest>>.failure(DioErrorMapper.map(error));
    } catch (error, stack) {
      return ApiResult<PageResult<WithdrawRequest>>.failure(
        UnknownException('Unexpected error.', cause: error, stackTrace: stack),
      );
    }
  }

  @override
  Future<ApiResult<WithdrawDetail>> fetchDetail(String uuid) {
    return _client.get<WithdrawDetail>(
      '/withdraw/$uuid',
      decoder: (data) => WithdrawDetail.fromJson(_map(data)),
    );
  }

  @override
  Future<ApiResult<CancelResult>> cancel(String uuid) {
    return _client.post<CancelResult>(
      '/withdraw/$uuid/cancel',
      decoder: (data) => CancelResult.fromJson(_map(data)),
    );
  }
}

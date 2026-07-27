import 'package:dio/dio.dart';

import '../../../core/error/app_exception.dart';
import '../../../core/network/api_client.dart';
import '../../../core/network/api_result.dart';
import '../../../core/network/dio_error_mapper.dart';
import '../models/conversion_rate.dart';
import '../models/transaction_filter.dart';
import '../models/transaction_page.dart';
import '../models/wallet_summary.dart';
import '../models/wallet_transaction.dart';
import 'wallet_repository.dart';

/// [WalletRepository] over the foundation [ApiClient].
///
/// The transaction list needs the response `meta.pagination` (which the shared
/// [ApiClient] unwraps away), so it reads the full envelope via [ApiClient.raw]
/// and reuses [DioErrorMapper] for error translation — the foundation itself is
/// not modified.
class WalletRepositoryImpl implements WalletRepository {
  WalletRepositoryImpl(this._client);

  final ApiClient _client;

  static Map<String, dynamic> _map(dynamic data) =>
      data is Map<String, dynamic> ? data : const <String, dynamic>{};

  @override
  Future<ApiResult<WalletSummary>> fetchSummary() {
    return _client.get<WalletSummary>(
      '/wallet',
      decoder: (data) => WalletSummary.fromJson(_map(data)),
    );
  }

  @override
  Future<ApiResult<ConversionRate>> fetchConversion() {
    return _client.get<ConversionRate>(
      '/wallet/conversion',
      decoder: (data) => ConversionRate.fromJson(_map(data)),
    );
  }

  @override
  Future<ApiResult<WalletTransaction>> fetchTransaction(String uuid) {
    return _client.get<WalletTransaction>(
      '/wallet/transactions/$uuid',
      decoder: (data) => WalletTransaction.fromJson(_map(data)),
    );
  }

  @override
  Future<ApiResult<TransactionPage>> fetchTransactions({
    TransactionFilter filter = TransactionFilter.none,
    String? cursor,
    int limit = 20,
  }) async {
    final query = <String, dynamic>{
      'limit': limit,
      if (cursor != null) 'cursor': cursor,
      ...filter.toQuery(),
    };

    try {
      final response = await _client.raw.get<dynamic>(
        '/wallet/transactions',
        queryParameters: query,
      );
      return ApiResult<TransactionPage>.success(_parsePage(response.data));
    } on DioException catch (error) {
      return ApiResult<TransactionPage>.failure(DioErrorMapper.map(error));
    } catch (error, stack) {
      return ApiResult<TransactionPage>.failure(
        UnknownException('Unexpected error.', cause: error, stackTrace: stack),
      );
    }
  }

  TransactionPage _parsePage(dynamic body) {
    final envelope = _map(body);
    final rawItems = envelope['data'];
    final items = rawItems is List
        ? rawItems
            .whereType<Map<String, dynamic>>()
            .map(WalletTransaction.fromJson)
            .toList(growable: false)
        : const <WalletTransaction>[];

    final pagination = _map(_map(envelope['meta'])['pagination']);
    final nextCursor = pagination['next_cursor'] as String?;
    final hasMore = pagination['has_more'] as bool? ?? (nextCursor != null);

    return TransactionPage(
      items: items,
      nextCursor: nextCursor,
      hasMore: hasMore,
    );
  }
}

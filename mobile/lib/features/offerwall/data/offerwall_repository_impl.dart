import 'package:dio/dio.dart';

import '../../../core/error/app_exception.dart';
import '../../../core/network/api_client.dart';
import '../../../core/network/api_result.dart';
import '../../../core/network/dio_error_mapper.dart';
import '../models/click_result.dart';
import '../models/offer.dart';
import '../models/offer_filter.dart';
import '../models/offer_provider.dart';
import '../models/offerwall_history_entry.dart';
import '../models/page_result.dart';
import 'offerwall_repository.dart';

/// [OfferwallRepository] over the foundation [ApiClient].
///
/// Paginated catalogs need `meta.pagination` (which the shared [ApiClient]
/// unwraps away), so they read the full envelope via [ApiClient.raw] and reuse
/// [DioErrorMapper]; the foundation is not modified.
class OfferwallRepositoryImpl implements OfferwallRepository {
  OfferwallRepositoryImpl(this._client);

  final ApiClient _client;

  static Map<String, dynamic> _map(dynamic data) =>
      data is Map<String, dynamic> ? data : const <String, dynamic>{};

  static List<T> _list<T>(dynamic data, T Function(Map<String, dynamic>) fromJson) {
    if (data is! List) {
      return <T>[];
    }
    return data.whereType<Map<String, dynamic>>().map(fromJson).toList(growable: false);
  }

  Future<ApiResult<PageResult<T>>> _fetchPage<T>(
    String path,
    Map<String, dynamic> query,
    T Function(Map<String, dynamic>) fromJson,
  ) async {
    try {
      final response = await _client.raw.get<dynamic>(path, queryParameters: query);
      final envelope = _map(response.data);
      final items = _list(envelope['data'], fromJson);
      final pagination = _map(_map(envelope['meta'])['pagination']);
      final nextCursor = pagination['next_cursor'] as String?;
      final hasMore = pagination['has_more'] as bool? ?? (nextCursor != null);
      return ApiResult<PageResult<T>>.success(
        PageResult<T>(items: items, nextCursor: nextCursor, hasMore: hasMore),
      );
    } on DioException catch (error) {
      return ApiResult<PageResult<T>>.failure(DioErrorMapper.map(error));
    } catch (error, stack) {
      return ApiResult<PageResult<T>>.failure(
        UnknownException('Unexpected error.', cause: error, stackTrace: stack),
      );
    }
  }

  @override
  Future<ApiResult<List<OfferProvider>>> fetchProviders() {
    return _client.get<List<OfferProvider>>(
      '/offerwall/providers',
      decoder: (data) => _list(data, OfferProvider.fromJson),
    );
  }

  @override
  Future<ApiResult<PageResult<Offer>>> fetchOffers({
    OfferFilter filter = OfferFilter.none,
    String? cursor,
    int limit = 20,
  }) {
    return _fetchPage<Offer>(
      '/offerwall/offers',
      {'limit': limit, if (cursor != null) 'cursor': cursor, ...filter.toQuery()},
      (json) => Offer.fromJson(json),
    );
  }

  @override
  Future<ApiResult<Offer>> fetchOffer(String uuid) {
    return _client.get<Offer>(
      '/offerwall/offers/$uuid',
      decoder: (data) => Offer.fromJson(_map(data)),
    );
  }

  @override
  Future<ApiResult<ClickResult>> clickOffer(String uuid) {
    return _client.post<ClickResult>(
      '/offerwall/offers/$uuid/click',
      decoder: (data) => ClickResult.fromJson(_map(data)),
    );
  }

  @override
  Future<ApiResult<PageResult<Offer>>> fetchCpaOffers({
    OfferFilter filter = OfferFilter.none,
    String? cursor,
    int limit = 20,
  }) {
    return _fetchPage<Offer>(
      '/cpa/offers',
      {'limit': limit, if (cursor != null) 'cursor': cursor, ...filter.toQuery()},
      (json) => Offer.fromJson(json, source: OfferSource.cpa),
    );
  }

  @override
  Future<ApiResult<Offer>> fetchCpaOffer(String uuid) {
    return _client.get<Offer>(
      '/cpa/offers/$uuid',
      decoder: (data) => Offer.fromJson(_map(data), source: OfferSource.cpa),
    );
  }

  @override
  Future<ApiResult<PageResult<OfferwallHistoryEntry>>> fetchHistory({
    String? cursor,
    int limit = 20,
  }) {
    return _fetchPage<OfferwallHistoryEntry>(
      '/wallet/transactions',
      {
        'limit': limit,
        if (cursor != null) 'cursor': cursor,
        'filter[type]': 'offerwall,cpa',
        'filter[direction]': 'credit',
      },
      OfferwallHistoryEntry.fromJson,
    );
  }
}

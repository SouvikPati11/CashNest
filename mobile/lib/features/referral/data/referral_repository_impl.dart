import 'package:dio/dio.dart';

import '../../../core/error/app_exception.dart';
import '../../../core/network/api_client.dart';
import '../../../core/network/api_result.dart';
import '../../../core/network/dio_error_mapper.dart';
import '../../offerwall/models/page_result.dart';
import '../models/leaderboard_entry.dart';
import '../models/referral_earning.dart';
import '../models/referral_entry.dart';
import '../models/referral_info.dart';
import 'referral_repository.dart';

/// [ReferralRepository] over the foundation [ApiClient]. Paginated lists read
/// the full envelope via [ApiClient.raw] for `meta.pagination` and reuse
/// [DioErrorMapper]; the foundation is not modified.
class ReferralRepositoryImpl implements ReferralRepository {
  ReferralRepositoryImpl(this._client);

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
  Future<ApiResult<ReferralInfo>> fetchInfo() {
    return _client.get<ReferralInfo>(
      '/referral',
      decoder: (data) => ReferralInfo.fromJson(_map(data)),
    );
  }

  @override
  Future<ApiResult<PageResult<ReferralEntry>>> fetchReferrals({
    String? status,
    String? cursor,
    int limit = 20,
  }) {
    return _fetchPage<ReferralEntry>(
      '/referral/list',
      {
        'limit': limit,
        if (cursor != null) 'cursor': cursor,
        if (status != null) 'filter[status]': status,
      },
      ReferralEntry.fromJson,
    );
  }

  @override
  Future<ApiResult<PageResult<ReferralEarning>>> fetchEarnings({String? cursor, int limit = 20}) {
    return _fetchPage<ReferralEarning>(
      '/referral/earnings',
      {'limit': limit, if (cursor != null) 'cursor': cursor},
      ReferralEarning.fromJson,
    );
  }

  @override
  Future<ApiResult<bool>> applyCode(String code) {
    return _client.post<bool>(
      '/referral/apply',
      body: {'referral_code': code},
      decoder: (data) => _map(data)['applied'] == true,
    );
  }

  @override
  Future<ApiResult<PageResult<LeaderboardEntry>>> fetchLeaderboard({
    String period = 'weekly',
    String? cursor,
    int limit = 20,
  }) {
    return _fetchPage<LeaderboardEntry>(
      '/leaderboard',
      {'limit': limit, if (cursor != null) 'cursor': cursor, 'filter[period]': period},
      LeaderboardEntry.fromJson,
    );
  }

  @override
  Future<ApiResult<LeaderboardMe>> fetchMyRank({String period = 'weekly'}) {
    return _client.get<LeaderboardMe>(
      '/leaderboard/me',
      query: {'filter[period]': period},
      decoder: (data) => LeaderboardMe.fromJson(_map(data)),
    );
  }
}

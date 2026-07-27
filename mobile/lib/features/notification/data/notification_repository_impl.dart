import 'package:dio/dio.dart';

import '../../../core/error/app_exception.dart';
import '../../../core/network/api_client.dart';
import '../../../core/network/api_result.dart';
import '../../../core/network/dio_error_mapper.dart';
import '../../offerwall/models/page_result.dart';
import '../models/app_notification.dart';
import '../models/notification_preferences.dart';
import 'notification_repository.dart';

/// [NotificationRepository] over the foundation [ApiClient]. The paginated inbox
/// reads the full envelope via [ApiClient.raw] for `meta.pagination` and reuses
/// [DioErrorMapper]. Preferences map to the notification subset of `/settings`.
/// The foundation and the Settings module are not modified.
class NotificationRepositoryImpl implements NotificationRepository {
  NotificationRepositoryImpl(this._client);

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
  Future<ApiResult<PageResult<AppNotification>>> fetchNotifications({
    String? type,
    bool? isRead,
    String? cursor,
    int limit = 20,
  }) async {
    final query = <String, dynamic>{
      'limit': limit,
      if (cursor != null) 'cursor': cursor,
      if (type != null) 'filter[type]': type,
      if (isRead != null) 'filter[is_read]': isRead,
    };
    try {
      final response = await _client.raw.get<dynamic>('/notifications', queryParameters: query);
      final envelope = _map(response.data);
      final items = _list(envelope['data'], AppNotification.fromJson);
      final pagination = _map(_map(envelope['meta'])['pagination']);
      final nextCursor = pagination['next_cursor'] as String?;
      final hasMore = pagination['has_more'] as bool? ?? (nextCursor != null);
      return ApiResult<PageResult<AppNotification>>.success(
        PageResult<AppNotification>(items: items, nextCursor: nextCursor, hasMore: hasMore),
      );
    } on DioException catch (error) {
      return ApiResult<PageResult<AppNotification>>.failure(DioErrorMapper.map(error));
    } catch (error, stack) {
      return ApiResult<PageResult<AppNotification>>.failure(
        UnknownException('Unexpected error.', cause: error, stackTrace: stack),
      );
    }
  }

  @override
  Future<ApiResult<int>> fetchUnreadCount() {
    return _client.get<int>(
      '/notifications/unread-count',
      decoder: (data) => (_map(data)['unread'] as num?)?.toInt() ?? 0,
    );
  }

  @override
  Future<ApiResult<bool>> markRead(String uuid) {
    return _client.post<bool>(
      '/notifications/$uuid/read',
      decoder: (data) => _map(data)['is_read'] as bool? ?? true,
    );
  }

  @override
  Future<ApiResult<int>> markAllRead() {
    return _client.post<int>(
      '/notifications/read-all',
      decoder: (data) => (_map(data)['updated'] as num?)?.toInt() ?? 0,
    );
  }

  @override
  Future<ApiResult<NotificationPreferences>> fetchPreferences() {
    return _client.get<NotificationPreferences>(
      '/settings',
      decoder: (data) => NotificationPreferences.fromJson(_map(data)),
    );
  }

  @override
  Future<ApiResult<NotificationPreferences>> updatePreferences(NotificationPreferences prefs) {
    return _client.put<NotificationPreferences>(
      '/settings',
      body: prefs.toJson(),
      decoder: (data) => NotificationPreferences.fromJson(_map(data)),
    );
  }
}

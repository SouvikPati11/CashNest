import 'package:cashnest/core/network/api_result.dart';
import 'package:cashnest/features/notification/data/notification_repository.dart';
import 'package:cashnest/features/notification/models/app_notification.dart';
import 'package:cashnest/features/notification/models/notification_preferences.dart';
import 'package:cashnest/features/offerwall/models/page_result.dart';

/// A configurable fake [NotificationRepository] for controller tests.
class FakeNotificationRepository implements NotificationRepository {
  ApiResult<NotificationPreferences> preferences =
      const ApiResult.success(NotificationPreferences());
  ApiResult<NotificationPreferences> updateResult =
      const ApiResult.success(NotificationPreferences(promotional: false));

  bool? lastIsReadFilter;
  int markReadCalls = 0;
  int markAllCalls = 0;
  NotificationPreferences? lastUpdated;

  @override
  Future<ApiResult<PageResult<AppNotification>>> fetchNotifications({
    String? type,
    bool? isRead,
    String? cursor,
    int limit = 20,
  }) async {
    lastIsReadFilter = isRead;
    return const ApiResult.success(PageResult(items: [
      AppNotification(uuid: 'n1', title: 'Welcome', body: 'Hi', isRead: false),
      AppNotification(uuid: 'n2', title: 'Reward', body: 'You earned coins', isRead: false),
    ], hasMore: false));
  }

  @override
  Future<ApiResult<int>> fetchUnreadCount() async => const ApiResult.success(2);

  @override
  Future<ApiResult<bool>> markRead(String uuid) async {
    markReadCalls++;
    return const ApiResult.success(true);
  }

  @override
  Future<ApiResult<int>> markAllRead() async {
    markAllCalls++;
    return const ApiResult.success(2);
  }

  @override
  Future<ApiResult<NotificationPreferences>> fetchPreferences() async => preferences;

  @override
  Future<ApiResult<NotificationPreferences>> updatePreferences(NotificationPreferences prefs) async {
    lastUpdated = prefs;
    return updateResult;
  }
}

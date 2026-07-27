import '../../../core/network/api_result.dart';
import '../../offerwall/models/page_result.dart';
import '../models/app_notification.dart';
import '../models/notification_preferences.dart';

/// Contract for the notification inbox, unread badge, read mutations, and
/// notification preferences.
abstract interface class NotificationRepository {
  Future<ApiResult<PageResult<AppNotification>>> fetchNotifications({
    String? type,
    bool? isRead,
    String? cursor,
    int limit = 20,
  });

  Future<ApiResult<int>> fetchUnreadCount();

  Future<ApiResult<bool>> markRead(String uuid);

  Future<ApiResult<int>> markAllRead();

  Future<ApiResult<NotificationPreferences>> fetchPreferences();

  Future<ApiResult<NotificationPreferences>> updatePreferences(NotificationPreferences prefs);
}

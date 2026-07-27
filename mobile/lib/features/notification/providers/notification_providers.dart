import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../app/di/providers.dart';
import '../../offerwall/application/paginated_list_state.dart';
import '../application/notifications_controller.dart';
import '../application/preferences_controller.dart';
import '../data/notification_repository.dart';
import '../data/notification_repository_impl.dart';
import '../models/app_notification.dart';
import '../models/notification_preferences.dart';

/// Riverpod wiring for the notification feature. Reuses the foundation API
/// client and shared paginated infrastructure; no completed module is modified.

final notificationRepositoryProvider = Provider<NotificationRepository>(
  (ref) => NotificationRepositoryImpl(ref.watch(apiClientProvider)),
);

final notificationsControllerProvider =
    StateNotifierProvider<NotificationsController, PaginatedListState<AppNotification>>(
  (ref) => NotificationsController(ref.watch(notificationRepositoryProvider)),
);

/// Unread count for the badge. Invalidate after read mutations to refresh.
final unreadCountProvider = FutureProvider<int>((ref) async {
  final result = await ref.watch(notificationRepositoryProvider).fetchUnreadCount();
  return result.dataOrNull ?? 0;
});

final preferencesControllerProvider =
    StateNotifierProvider<PreferencesController, AsyncValue<NotificationPreferences>>(
  (ref) => PreferencesController(ref.watch(notificationRepositoryProvider)),
);

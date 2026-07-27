import '../../../core/network/api_result.dart';
import '../../offerwall/application/paginated_list_controller.dart';
import '../../offerwall/models/page_result.dart';
import '../data/notification_repository.dart';
import '../models/app_notification.dart';

/// Paginated notification inbox with an unread-only filter and read mutations.
/// Reuses the shared paginated-list infrastructure from the offerwall feature.
class NotificationsController extends PaginatedListController<AppNotification> {
  NotificationsController(this._repository);

  final NotificationRepository _repository;

  bool _unreadOnly = false;
  bool get unreadOnly => _unreadOnly;

  @override
  Future<ApiResult<PageResult<AppNotification>>> fetchPage({String? cursor}) {
    return _repository.fetchNotifications(
      isRead: _unreadOnly ? false : null,
      cursor: cursor,
      limit: pageSize,
    );
  }

  Future<void> setUnreadOnly(bool value) async {
    if (_unreadOnly == value) {
      return;
    }
    _unreadOnly = value;
    await load();
  }

  /// Mark one notification read and reflect it locally. Returns true on success.
  Future<bool> markRead(String uuid) async {
    final result = await _repository.markRead(uuid);
    if (result case ApiSuccess()) {
      _updateOne(uuid);
      return true;
    }
    return false;
  }

  /// Mark all notifications read and reflect it locally.
  Future<void> markAllRead() async {
    final result = await _repository.markAllRead();
    if (result case ApiSuccess()) {
      state = state.copyWith(
        items: [for (final n in state.items) n.copyWith(isRead: true)],
      );
      // If filtering unread-only, the list is now empty of unread items.
      if (_unreadOnly) {
        await load();
      }
    }
  }

  void _updateOne(String uuid) {
    state = state.copyWith(
      items: [
        for (final n in state.items)
          if (n.uuid == uuid) n.copyWith(isRead: true) else n,
      ],
    );
  }
}

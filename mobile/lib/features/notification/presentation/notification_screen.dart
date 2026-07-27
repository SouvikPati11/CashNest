import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_spacing.dart';
import '../../../shared/widgets/app_scaffold.dart';
import '../../offerwall/presentation/widgets/paginated_list_view.dart';
import '../l10n/notification_strings.dart';
import '../models/app_notification.dart';
import '../providers/notification_providers.dart';
import 'notification_detail_screen.dart';
import 'notification_preferences_screen.dart';
import 'widgets/notification_tile.dart';

/// The notification inbox: all/unread filter, mark-all-read, pagination.
class NotificationScreen extends ConsumerStatefulWidget {
  const NotificationScreen({super.key});

  @override
  ConsumerState<NotificationScreen> createState() => _NotificationScreenState();
}

class _NotificationScreenState extends ConsumerState<NotificationScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(notificationsControllerProvider.notifier).load();
    });
  }

  Future<void> _markAllRead() async {
    final s = NotificationStrings.of(context);
    await ref.read(notificationsControllerProvider.notifier).markAllRead();
    if (!mounted) {
      return;
    }
    ref.invalidate(unreadCountProvider);
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text(s.allMarkedRead)));
  }

  void _openPreferences() {
    Navigator.of(context).push(
      MaterialPageRoute<void>(builder: (_) => const NotificationPreferencesScreen()),
    );
  }

  @override
  Widget build(BuildContext context) {
    final s = NotificationStrings.of(context);
    final state = ref.watch(notificationsControllerProvider);
    final controller = ref.read(notificationsControllerProvider.notifier);
    final unreadOnly = controller.unreadOnly;

    return AppScaffold(
      appBar: AppBar(
        title: Text(s.title),
        actions: [
          IconButton(
            tooltip: s.markAllRead,
            onPressed: _markAllRead,
            icon: const Icon(Icons.done_all),
          ),
          IconButton(
            tooltip: s.preferences,
            onPressed: _openPreferences,
            icon: const Icon(Icons.tune),
          ),
        ],
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(
              AppSpacing.screen,
              AppSpacing.sm,
              AppSpacing.screen,
              AppSpacing.sm,
            ),
            child: Row(
              children: [
                ChoiceChip(
                  label: Text(s.all),
                  selected: !unreadOnly,
                  onSelected: (_) => controller.setUnreadOnly(false),
                ),
                const SizedBox(width: AppSpacing.sm),
                ChoiceChip(
                  label: Text(s.unread),
                  selected: unreadOnly,
                  onSelected: (_) => controller.setUnreadOnly(true),
                ),
              ],
            ),
          ),
          Expanded(
            child: PaginatedListView<AppNotification>(
              state: state,
              visibleItems: controller.visibleItems,
              isFiltering: unreadOnly,
              onRefresh: () async {
                await controller.refresh();
                if (mounted) {
                  ref.invalidate(unreadCountProvider);
                }
              },
              onLoadMore: controller.loadMore,
              onRetryLoadMore: controller.retryLoadMore,
              onRetry: controller.load,
              emptyIcon: Icons.notifications_none,
              emptyTitle: s.noNotifications,
              emptyBody: s.noNotificationsBody,
              noResultsTitle: s.noUnread,
              noResultsBody: s.noUnreadBody,
              loadMoreErrorText: s.loadMoreError,
              skeletonItemHeight: 88,
              itemBuilder: (context, notification) => NotificationTile(
                notification: notification,
                onTap: () => Navigator.of(context).push(
                  MaterialPageRoute<void>(
                    builder: (_) => NotificationDetailScreen(notification: notification),
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

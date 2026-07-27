import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_radius.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../shared/extensions/context_extensions.dart';
import '../../../shared/widgets/app_scaffold.dart';
import '../l10n/notification_strings.dart';
import '../models/app_notification.dart';
import '../providers/notification_providers.dart';
import '../resources/notification_visuals.dart';

/// Notification detail; marks the notification read on open.
class NotificationDetailScreen extends ConsumerStatefulWidget {
  const NotificationDetailScreen({required this.notification, super.key});

  final AppNotification notification;

  @override
  ConsumerState<NotificationDetailScreen> createState() => _NotificationDetailScreenState();
}

class _NotificationDetailScreenState extends ConsumerState<NotificationDetailScreen> {
  @override
  void initState() {
    super.initState();
    if (!widget.notification.isRead) {
      WidgetsBinding.instance.addPostFrameCallback((_) async {
        final ok = await ref
            .read(notificationsControllerProvider.notifier)
            .markRead(widget.notification.uuid);
        if (ok && mounted) {
          ref.invalidate(unreadCountProvider);
        }
      });
    }
  }

  Future<void> _copyLink(String link) async {
    final s = NotificationStrings.of(context);
    await Clipboard.setData(ClipboardData(text: link));
    if (mounted) {
      ScaffoldMessenger.of(context)
        ..hideCurrentSnackBar()
        ..showSnackBar(SnackBar(content: Text(s.linkCopied)));
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = NotificationStrings.of(context);
    final n = widget.notification;

    return AppScaffold(
      appBar: AppBar(title: Text(s.detailTitle)),
      body: ListView(
        padding: const EdgeInsets.all(AppSpacing.screen),
        children: [
          Row(
            children: [
              CircleAvatar(
                backgroundColor: context.colors.secondaryContainer,
                child: Icon(
                  NotificationVisuals.iconFor(n.type),
                  color: context.colors.onSecondaryContainer,
                ),
              ),
              const SizedBox(width: AppSpacing.md),
              Expanded(child: Text(n.title, style: context.textTheme.titleLarge)),
            ],
          ),
          if (n.createdAt != null) ...[
            const SizedBox(height: AppSpacing.xs),
            Text(
              NotificationVisuals.dateTime(n.createdAt!),
              style: context.textTheme.bodySmall?.copyWith(color: context.colors.onSurfaceVariant),
            ),
          ],
          if (n.imageUrl != null && n.imageUrl!.isNotEmpty) ...[
            const SizedBox(height: AppSpacing.lg),
            ClipRRect(
              borderRadius: AppRadius.lgAll,
              child: Image.network(
                n.imageUrl!,
                fit: BoxFit.cover,
                errorBuilder: (context, error, stack) => const SizedBox.shrink(),
              ),
            ),
          ],
          const SizedBox(height: AppSpacing.lg),
          Text(n.body, style: context.textTheme.bodyLarge),
          if (n.deepLink != null && n.deepLink!.isNotEmpty) ...[
            const SizedBox(height: AppSpacing.xl),
            FilledButton.icon(
              onPressed: () => _copyLink(n.deepLink!),
              icon: const Icon(Icons.link),
              label: Text(s.openLink),
            ),
          ],
        ],
      ),
    );
  }
}

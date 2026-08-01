import 'package:flutter/material.dart';

import '../../../../core/theme/app_radius.dart';
import '../../../../core/theme/app_spacing.dart';
import '../../../../shared/extensions/context_extensions.dart';
import '../../l10n/home_strings.dart';
import '../../models/home_announcement.dart';

/// Shows the home announcement as a modal dialog.
///
/// Resolves to `true` when the user dismisses/acknowledges it (the caller should
/// then persist the seen state), and to `false`/`null` when it has an action the
/// user tapped or the barrier was dismissed. The [onAction] callback fires for
/// actionable announcements so the host can route by `actionType`.
Future<bool?> showHomeAnnouncement(
  BuildContext context,
  HomeAnnouncement announcement, {
  void Function(HomeAnnouncement announcement)? onAction,
}) {
  return showDialog<bool>(
    context: context,
    barrierDismissible: announcement.isDismissible,
    builder: (context) => _AnnouncementDialog(
      announcement: announcement,
      onAction: onAction,
    ),
  );
}

class _AnnouncementDialog extends StatelessWidget {
  const _AnnouncementDialog({required this.announcement, this.onAction});

  final HomeAnnouncement announcement;
  final void Function(HomeAnnouncement announcement)? onAction;

  bool get _hasAction => announcement.actionType != 'none' && announcement.actionType.isNotEmpty;

  @override
  Widget build(BuildContext context) {
    final s = HomeStrings.of(context);

    return AlertDialog(
      icon: Icon(Icons.campaign_outlined, color: context.colors.primary, size: 32),
      title: Text(announcement.title, textAlign: TextAlign.center),
      content: SingleChildScrollView(
        child: Text(announcement.body, style: context.textTheme.bodyMedium),
      ),
      actionsAlignment: MainAxisAlignment.center,
      actions: [
        // A "dismiss" affordance next to an action, so the action button is not
        // the only way to close an actionable announcement.
        if (_hasAction && announcement.isDismissible)
          TextButton(
            onPressed: () => Navigator.of(context).pop(true),
            child: Text(s.ok),
          ),
        if (_hasAction)
          FilledButton(
            onPressed: () {
              onAction?.call(announcement);
              Navigator.of(context).pop(true);
            },
            child: Text(s.open),
          )
        else
          FilledButton(
            onPressed: () => Navigator.of(context).pop(true),
            child: Text(s.ok),
          ),
      ],
    );
  }
}

/// A dismissible inline announcement banner (for `display_type: banner`).
class AnnouncementBanner extends StatelessWidget {
  const AnnouncementBanner({
    required this.announcement,
    this.onDismiss,
    this.onTap,
    super.key,
  });

  final HomeAnnouncement announcement;
  final VoidCallback? onDismiss;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: context.colors.secondaryContainer,
      borderRadius: AppRadius.lgAll,
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(AppSpacing.md),
          child: Row(
            children: [
              Icon(Icons.campaign_outlined, color: context.colors.onSecondaryContainer),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      announcement.title,
                      style: context.textTheme.titleSmall
                          ?.copyWith(color: context.colors.onSecondaryContainer),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    Text(
                      announcement.body,
                      style: context.textTheme.bodySmall
                          ?.copyWith(color: context.colors.onSecondaryContainer),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                ),
              ),
              if (announcement.isDismissible && onDismiss != null)
                IconButton(
                  icon: const Icon(Icons.close),
                  color: context.colors.onSecondaryContainer,
                  onPressed: onDismiss,
                ),
            ],
          ),
        ),
      ),
    );
  }
}

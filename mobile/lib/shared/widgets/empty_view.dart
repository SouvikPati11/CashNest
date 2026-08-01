import 'package:flutter/material.dart';

import '../../core/theme/app_spacing.dart';
import '../extensions/context_extensions.dart';
import 'ds/icon_badge.dart';

/// Inline empty state for lists/collections with no data — an [IconBadge], a
/// title and a message, styled consistently with the design system.
class EmptyView extends StatelessWidget {
  const EmptyView({
    this.title,
    this.message,
    this.icon = Icons.inbox_outlined,
    this.action,
    super.key,
  });

  final String? title;
  final String? message;
  final IconData icon;
  final Widget? action;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;

    return Center(
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.xl),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            IconBadge(icon: icon, size: 72, iconSize: 34, color: context.colors.primary),
            const SizedBox(height: AppSpacing.lg),
            Text(
              title ?? l10n.emptyTitle,
              style: context.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: AppSpacing.xs),
            Text(
              message ?? l10n.emptyMessage,
              style: context.textTheme.bodyMedium?.copyWith(color: context.colors.onSurfaceVariant),
              textAlign: TextAlign.center,
            ),
            if (action != null) ...[
              const SizedBox(height: AppSpacing.lg),
              action!,
            ],
          ],
        ),
      ),
    );
  }
}

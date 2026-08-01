import 'package:flutter/material.dart';

import '../../../core/theme/app_spacing.dart';
import '../../extensions/context_extensions.dart';
import 'icon_badge.dart';

/// The single list-row style for the whole app: a leading [IconBadge], a
/// title + optional subtitle, and an optional trailing widget (value text,
/// chevron, switch…). Used by Settings, Notifications, History, Wallet lists,
/// etc. so every row reads identically.
class AppListTile extends StatelessWidget {
  const AppListTile({
    required this.icon,
    required this.title,
    this.subtitle,
    this.trailing,
    this.onTap,
    this.iconColor,
    this.showChevron = false,
    this.padding = const EdgeInsets.symmetric(
      horizontal: AppSpacing.lg,
      vertical: AppSpacing.md,
    ),
    super.key,
  });

  final IconData icon;
  final String title;
  final String? subtitle;
  final Widget? trailing;
  final VoidCallback? onTap;
  final Color? iconColor;
  final bool showChevron;
  final EdgeInsetsGeometry padding;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      child: Padding(
        padding: padding,
        child: Row(
          children: [
            IconBadge(icon: icon, color: iconColor, size: 42, iconSize: 20),
            const SizedBox(width: AppSpacing.md),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: context.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w600),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  if (subtitle != null && subtitle!.isNotEmpty) ...[
                    const SizedBox(height: 2),
                    Text(
                      subtitle!,
                      style: context.textTheme.bodySmall
                          ?.copyWith(color: context.colors.onSurfaceVariant),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                ],
              ),
            ),
            if (trailing != null) ...[
              const SizedBox(width: AppSpacing.sm),
              trailing!,
            ],
            if (showChevron) ...[
              const SizedBox(width: AppSpacing.xs),
              Icon(Icons.chevron_right, color: context.colors.onSurfaceVariant),
            ],
          ],
        ),
      ),
    );
  }
}

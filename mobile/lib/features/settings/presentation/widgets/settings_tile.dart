import 'package:flutter/material.dart';

import '../../../../core/theme/app_spacing.dart';
import '../../../../shared/extensions/context_extensions.dart';

/// A section header used to group settings tiles.
class SettingsSectionHeader extends StatelessWidget {
  const SettingsSectionHeader({required this.title, super.key});

  final String title;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(AppSpacing.screen, AppSpacing.lg, AppSpacing.screen, AppSpacing.sm),
      child: Text(
        title.toUpperCase(),
        style: context.textTheme.labelMedium?.copyWith(
          color: context.colors.primary,
          fontWeight: FontWeight.w700,
          letterSpacing: 0.8,
        ),
      ),
    );
  }
}

/// A single settings row with a leading icon, title, optional trailing value,
/// and a chevron.
class SettingsTile extends StatelessWidget {
  const SettingsTile({
    required this.icon,
    required this.title,
    this.value,
    this.onTap,
    this.trailing,
    this.destructive = false,
    super.key,
  });

  final IconData icon;
  final String title;
  final String? value;
  final VoidCallback? onTap;
  final Widget? trailing;
  final bool destructive;

  @override
  Widget build(BuildContext context) {
    final color = destructive ? context.colors.error : context.colors.onSurface;

    return ListTile(
      leading: Icon(icon, color: destructive ? context.colors.error : context.colors.primary),
      title: Text(title, style: context.textTheme.bodyLarge?.copyWith(color: color)),
      trailing: trailing ??
          Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              if (value != null)
                Text(
                  value!,
                  style: context.textTheme.bodyMedium?.copyWith(color: context.colors.onSurfaceVariant),
                ),
              if (onTap != null) ...[
                const SizedBox(width: AppSpacing.xs),
                Icon(Icons.chevron_right, color: context.colors.onSurfaceVariant),
              ],
            ],
          ),
      onTap: onTap,
    );
  }
}

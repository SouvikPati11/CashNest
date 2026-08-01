import 'package:flutter/material.dart';

import '../../../core/theme/app_spacing.dart';
import '../../extensions/context_extensions.dart';
import 'app_card.dart';
import 'icon_badge.dart';

/// A compact metric display: an icon, a big value and a caption. Designed to sit
/// in a row of equal-width tiles (wrap each in [Expanded]) for stat strips on
/// the wallet, referral and rewards screens.
class StatTile extends StatelessWidget {
  const StatTile({
    required this.icon,
    required this.value,
    required this.label,
    this.color,
    super.key,
  });

  final IconData icon;
  final String value;
  final String label;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    return AppCard(
      padding: const EdgeInsets.all(AppSpacing.md),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          IconBadge(icon: icon, color: color, size: 38, iconSize: 18),
          const SizedBox(height: AppSpacing.md),
          Text(
            value,
            style: context.textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
          const SizedBox(height: 2),
          Text(
            label,
            style: context.textTheme.bodySmall?.copyWith(color: context.colors.onSurfaceVariant),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }
}

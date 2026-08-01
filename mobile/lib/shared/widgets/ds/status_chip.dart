import 'package:flutter/material.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_radius.dart';
import '../../extensions/context_extensions.dart';

/// Semantic intent for a [StatusChip].
enum StatusTone { neutral, success, warning, danger, info, brand }

/// The single status-pill style: a tinted rounded chip with an optional dot.
/// Used for transaction/withdrawal/task states so every status reads the same.
class StatusChip extends StatelessWidget {
  const StatusChip({
    required this.label,
    this.tone = StatusTone.neutral,
    this.icon,
    this.dense = false,
    super.key,
  });

  final String label;
  final StatusTone tone;
  final IconData? icon;
  final bool dense;

  Color _color(BuildContext context) {
    switch (tone) {
      case StatusTone.success:
        return AppColors.success;
      case StatusTone.warning:
        return AppColors.warning;
      case StatusTone.danger:
        return context.colors.error;
      case StatusTone.info:
        return AppColors.info;
      case StatusTone.brand:
        return context.colors.primary;
      case StatusTone.neutral:
        return context.colors.onSurfaceVariant;
    }
  }

  @override
  Widget build(BuildContext context) {
    final color = _color(context);
    return Container(
      padding: EdgeInsets.symmetric(horizontal: dense ? 8 : 10, vertical: dense ? 3 : 5),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.14),
        borderRadius: AppRadius.pillAll,
        border: Border.all(color: color.withValues(alpha: 0.30)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (icon != null) ...[
            Icon(icon, size: dense ? 12 : 14, color: color),
            const SizedBox(width: 4),
          ] else ...[
            Container(
              width: 6,
              height: 6,
              margin: const EdgeInsets.only(right: 6),
              decoration: BoxDecoration(color: color, shape: BoxShape.circle),
            ),
          ],
          Text(
            label,
            style: (dense ? context.textTheme.labelSmall : context.textTheme.labelMedium)
                ?.copyWith(color: color, fontWeight: FontWeight.w600),
          ),
        ],
      ),
    );
  }
}

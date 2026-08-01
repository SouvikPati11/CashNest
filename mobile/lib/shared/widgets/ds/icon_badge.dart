import 'package:flutter/material.dart';

import '../../../core/theme/app_radius.dart';
import '../../extensions/context_extensions.dart';

/// The single icon-chip style for the whole app: a gradient-tinted rounded
/// square with a hairline border. Used as leading art on cards, list rows,
/// quick actions and empty states so every icon reads the same.
class IconBadge extends StatelessWidget {
  const IconBadge({
    required this.icon,
    this.color,
    this.size = 48,
    this.iconSize,
    this.radius = AppRadius.mdAll,
    super.key,
  });

  final IconData icon;
  final Color? color;
  final double size;
  final double? iconSize;
  final BorderRadius radius;

  @override
  Widget build(BuildContext context) {
    final accent = color ?? context.colors.primary;
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            accent.withValues(alpha: 0.28),
            accent.withValues(alpha: 0.12),
          ],
        ),
        borderRadius: radius,
        border: Border.all(color: accent.withValues(alpha: 0.22)),
      ),
      child: Icon(icon, color: accent, size: iconSize ?? size * 0.5),
    );
  }
}

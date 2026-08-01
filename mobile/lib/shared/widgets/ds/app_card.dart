import 'package:flutter/material.dart';

import '../../../core/theme/app_gradients.dart';
import '../../../core/theme/app_radius.dart';
import '../../../core/theme/app_spacing.dart';
import '../../extensions/context_extensions.dart';

/// The single surface/card style for the whole app.
///
/// Replaces raw [Card] / [Container] usage so every panel shares the same
/// radius, border, padding and tap feedback. Pass [gradient] for a hero/CTA
/// surface (with an optional coloured [glowColor]); otherwise it renders the
/// themed elevated surface with a hairline border.
class AppCard extends StatelessWidget {
  const AppCard({
    required this.child,
    this.padding = const EdgeInsets.all(AppSpacing.lg),
    this.onTap,
    this.gradient,
    this.glowColor,
    this.borderRadius = AppRadius.lgAll,
    this.color,
    this.border = true,
    super.key,
  });

  final Widget child;
  final EdgeInsetsGeometry padding;
  final VoidCallback? onTap;
  final Gradient? gradient;
  final Color? glowColor;
  final BorderRadius borderRadius;
  final Color? color;
  final bool border;

  @override
  Widget build(BuildContext context) {
    final scheme = context.colors;
    final isGradient = gradient != null;
    final surface = color ?? scheme.surface;

    final decorated = DecoratedBox(
      decoration: BoxDecoration(
        color: isGradient ? null : surface,
        gradient: gradient,
        borderRadius: borderRadius,
        border: (border && !isGradient)
            ? Border.all(color: scheme.outlineVariant)
            : null,
        boxShadow: glowColor != null ? AppGradients.glow(glowColor!) : null,
      ),
      child: Material(
        type: MaterialType.transparency,
        child: InkWell(
          onTap: onTap,
          borderRadius: borderRadius,
          child: Padding(padding: padding, child: child),
        ),
      ),
    );

    return ClipRRect(borderRadius: borderRadius, child: decorated);
  }
}

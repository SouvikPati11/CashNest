import 'package:flutter/material.dart';

import '../../../core/theme/app_gradients.dart';
import '../../../core/theme/app_radius.dart';
import '../../../core/theme/app_spacing.dart';
import '../../extensions/context_extensions.dart';

/// A reusable brand-gradient hero panel used at the top of feature screens
/// (wallet, rewards, referral…) to establish a consistent premium header. Shows
/// a title, optional subtitle, an optional leading [IconData], and an optional
/// [child] (e.g. a big value or a stat row) laid over decorative blooms.
class GradientHero extends StatelessWidget {
  const GradientHero({
    required this.title,
    this.subtitle,
    this.icon,
    this.child,
    this.padding = const EdgeInsets.all(AppSpacing.xl),
    super.key,
  });

  final String title;
  final String? subtitle;
  final IconData? icon;
  final Widget? child;
  final EdgeInsetsGeometry padding;

  @override
  Widget build(BuildContext context) {
    const onGradient = Colors.white;
    return DecoratedBox(
      decoration: BoxDecoration(
        borderRadius: AppRadius.xlAll,
        boxShadow: AppGradients.glow(context.colors.primary, opacity: 0.4),
      ),
      child: ClipRRect(
        borderRadius: AppRadius.xlAll,
        child: Stack(
          children: [
            const Positioned.fill(
              child: DecoratedBox(decoration: BoxDecoration(gradient: AppGradients.hero)),
            ),
            Positioned(
              top: -40,
              right: -24,
              child: _Bloom(size: 140, color: Colors.white.withValues(alpha: 0.13)),
            ),
            Positioned(
              bottom: -50,
              left: -30,
              child: _Bloom(size: 130, color: Colors.black.withValues(alpha: 0.10)),
            ),
            Padding(
              padding: padding,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      if (icon != null) ...[
                        Container(
                          padding: const EdgeInsets.all(8),
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.18),
                            borderRadius: AppRadius.smAll,
                          ),
                          child: Icon(icon, color: onGradient, size: 18),
                        ),
                        const SizedBox(width: AppSpacing.sm),
                      ],
                      Expanded(
                        child: Text(
                          title,
                          style: context.textTheme.titleMedium?.copyWith(
                            color: onGradient,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                    ],
                  ),
                  if (subtitle != null && subtitle!.isNotEmpty) ...[
                    const SizedBox(height: AppSpacing.xs),
                    Text(
                      subtitle!,
                      style: context.textTheme.bodyMedium?.copyWith(
                        color: onGradient.withValues(alpha: 0.9),
                      ),
                    ),
                  ],
                  if (child != null) ...[
                    const SizedBox(height: AppSpacing.lg),
                    DefaultTextStyle.merge(
                      style: const TextStyle(color: onGradient),
                      child: child!,
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _Bloom extends StatelessWidget {
  const _Bloom({required this.size, required this.color});

  final double size;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(shape: BoxShape.circle, color: color),
    );
  }
}

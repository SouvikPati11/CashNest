import 'package:flutter/material.dart';

import '../../../../core/theme/app_radius.dart';
import '../../../../core/theme/app_spacing.dart';
import '../../../../shared/extensions/context_extensions.dart';

/// A shimmering placeholder box used while the dashboard loads.
class SkeletonBox extends StatefulWidget {
  const SkeletonBox({
    required this.width,
    required this.height,
    this.borderRadius = AppRadius.smAll,
    super.key,
  });

  final double width;
  final double height;
  final BorderRadius borderRadius;

  @override
  State<SkeletonBox> createState() => _SkeletonBoxState();
}

class _SkeletonBoxState extends State<SkeletonBox> with SingleTickerProviderStateMixin {
  late final AnimationController _controller = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 1200),
  )..repeat(reverse: true);

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final base = context.colors.surfaceContainerHighest;
    final highlight = context.colors.surfaceContainerHigh;

    return AnimatedBuilder(
      animation: _controller,
      builder: (context, child) {
        return Container(
          width: widget.width,
          height: widget.height,
          decoration: BoxDecoration(
            borderRadius: widget.borderRadius,
            color: Color.lerp(base, highlight, _controller.value),
          ),
        );
      },
    );
  }
}

/// Full-dashboard skeleton shown during the initial load.
class HomeSkeleton extends StatelessWidget {
  const HomeSkeleton({super.key});

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(AppSpacing.screen),
      physics: const NeverScrollableScrollPhysics(),
      children: const [
        Row(
          children: [
            SkeletonBox(width: 48, height: 48, borderRadius: AppRadius.pillAll),
            SizedBox(width: AppSpacing.md),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  SkeletonBox(width: 80, height: 12),
                  SizedBox(height: AppSpacing.xs),
                  SkeletonBox(width: 140, height: 18),
                ],
              ),
            ),
          ],
        ),
        SizedBox(height: AppSpacing.xl),
        SkeletonBox(width: double.infinity, height: 150, borderRadius: AppRadius.lgAll),
        SizedBox(height: AppSpacing.xl),
        SkeletonBox(width: 120, height: 16),
        SizedBox(height: AppSpacing.md),
        SkeletonBox(width: double.infinity, height: 80, borderRadius: AppRadius.lgAll),
        SizedBox(height: AppSpacing.lg),
        SkeletonBox(width: double.infinity, height: 88, borderRadius: AppRadius.lgAll),
        SizedBox(height: AppSpacing.lg),
        SkeletonBox(width: double.infinity, height: 88, borderRadius: AppRadius.lgAll),
      ],
    );
  }
}

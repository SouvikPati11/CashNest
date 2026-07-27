import 'package:flutter/material.dart';

import '../../../../core/theme/app_radius.dart';
import '../../../../core/theme/app_spacing.dart';
import '../../../../shared/extensions/context_extensions.dart';

/// A shimmering placeholder box used while rewards data loads.
class RewardSkeletonBox extends StatefulWidget {
  const RewardSkeletonBox({
    required this.width,
    required this.height,
    this.borderRadius = AppRadius.smAll,
    super.key,
  });

  final double width;
  final double height;
  final BorderRadius borderRadius;

  @override
  State<RewardSkeletonBox> createState() => _RewardSkeletonBoxState();
}

class _RewardSkeletonBoxState extends State<RewardSkeletonBox>
    with SingleTickerProviderStateMixin {
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

/// A list of card-shaped skeletons for the rewards sub-screens.
class RewardListSkeleton extends StatelessWidget {
  const RewardListSkeleton({this.itemCount = 5, this.itemHeight = 88, super.key});

  final int itemCount;
  final double itemHeight;

  @override
  Widget build(BuildContext context) {
    return ListView.separated(
      padding: const EdgeInsets.all(AppSpacing.screen),
      physics: const NeverScrollableScrollPhysics(),
      itemCount: itemCount,
      separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.md),
      itemBuilder: (_, __) => RewardSkeletonBox(
        width: double.infinity,
        height: itemHeight,
        borderRadius: AppRadius.lgAll,
      ),
    );
  }
}

import 'package:flutter/material.dart';

import '../../../../core/theme/app_radius.dart';
import '../../../../core/theme/app_spacing.dart';
import '../../../../shared/extensions/context_extensions.dart';

/// A shimmering placeholder box used while wallet data loads.
class WalletSkeletonBox extends StatefulWidget {
  const WalletSkeletonBox({
    required this.width,
    required this.height,
    this.borderRadius = AppRadius.smAll,
    super.key,
  });

  final double width;
  final double height;
  final BorderRadius borderRadius;

  @override
  State<WalletSkeletonBox> createState() => _WalletSkeletonBoxState();
}

class _WalletSkeletonBoxState extends State<WalletSkeletonBox>
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

/// A single skeleton row mirroring a transaction tile.
class TransactionTileSkeleton extends StatelessWidget {
  const TransactionTileSkeleton({super.key});

  @override
  Widget build(BuildContext context) {
    return const Padding(
      padding: EdgeInsets.symmetric(vertical: AppSpacing.sm),
      child: Row(
        children: [
          WalletSkeletonBox(width: 40, height: 40, borderRadius: AppRadius.smAll),
          SizedBox(width: AppSpacing.md),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                WalletSkeletonBox(width: 160, height: 14),
                SizedBox(height: AppSpacing.xs),
                WalletSkeletonBox(width: 90, height: 12),
              ],
            ),
          ),
          SizedBox(width: AppSpacing.md),
          WalletSkeletonBox(width: 48, height: 16),
        ],
      ),
    );
  }
}

/// Full wallet skeleton: balance card + a few transaction rows.
class WalletSkeleton extends StatelessWidget {
  const WalletSkeleton({super.key});

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(AppSpacing.screen),
      physics: const NeverScrollableScrollPhysics(),
      children: [
        const WalletSkeletonBox(
          width: double.infinity,
          height: 170,
          borderRadius: AppRadius.lgAll,
        ),
        const SizedBox(height: AppSpacing.lg),
        const WalletSkeletonBox(
          width: double.infinity,
          height: 88,
          borderRadius: AppRadius.lgAll,
        ),
        const SizedBox(height: AppSpacing.xl),
        const WalletSkeletonBox(width: 160, height: 18),
        const SizedBox(height: AppSpacing.md),
        for (var i = 0; i < 6; i++) const TransactionTileSkeleton(),
      ],
    );
  }
}

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/error/app_exception.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../shared/extensions/context_extensions.dart';
import '../../../shared/widgets/app_scaffold.dart';
import '../../../shared/widgets/empty_view.dart';
import '../l10n/rewards_strings.dart';
import '../models/spin_result.dart';
import '../models/spin_status.dart';
import '../providers/rewards_providers.dart';
import 'widgets/reward_error.dart';
import 'widgets/reward_result_dialog.dart';
import 'widgets/reward_skeleton.dart';
import 'widgets/spin_wheel_widget.dart';

/// Spin wheel: animates to the server-chosen segment, then shows the reward.
class SpinWheelScreen extends ConsumerStatefulWidget {
  const SpinWheelScreen({super.key});

  @override
  ConsumerState<SpinWheelScreen> createState() => _SpinWheelScreenState();
}

class _SpinWheelScreenState extends ConsumerState<SpinWheelScreen> {
  int _spinToken = 0;
  int? _targetIndex;
  bool _spinning = false;
  SpinResult? _pending;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(spinControllerProvider.notifier).load();
    });
  }

  void _snack(String message) {
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text(message)));
  }

  Future<void> _spin(SpinStatus status) async {
    if (_spinning || !status.canSpin) {
      return;
    }
    setState(() => _spinning = true);
    try {
      final result = await ref.read(spinControllerProvider.notifier).spin();
      if (!mounted) {
        return;
      }
      final index = status.segments.indexWhere((seg) => seg.id == result.segmentId);
      _pending = result;
      setState(() {
        _targetIndex = index >= 0 ? index : 0;
        _spinToken++;
      });
    } on AppException catch (e) {
      _pending = null;
      if (mounted) {
        setState(() => _spinning = false);
        _snack(e.message);
      }
    }
  }

  Future<void> _onSettled() async {
    final result = _pending;
    _pending = null;
    if (mounted) {
      setState(() => _spinning = false);
    }
    if (result != null && mounted) {
      final s = RewardsStrings.of(context);
      await showRewardResult(
        context,
        coins: result.rewardCoins,
        message: result.rewardCoins > 0 ? null : s.betterLuck,
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = RewardsStrings.of(context);
    final state = ref.watch(spinControllerProvider);

    return AppScaffold(
      appBar: AppBar(title: Text(s.spinWheel)),
      body: RefreshIndicator(
        onRefresh: () => ref.read(spinControllerProvider.notifier).refresh(),
        child: switch (state) {
          AsyncData(:final value) => _Body(
              status: value,
              spinning: _spinning,
              spinToken: _spinToken,
              targetIndex: _targetIndex,
              onSpin: () => _spin(value),
              onSettled: _onSettled,
            ),
          AsyncError(:final error) => RewardErrorView(
              error: error,
              onRetry: () => ref.read(spinControllerProvider.notifier).load(),
            ),
          _ => const RewardListSkeleton(itemCount: 2, itemHeight: 200),
        },
      ),
    );
  }
}

class _Body extends StatelessWidget {
  const _Body({
    required this.status,
    required this.spinning,
    required this.spinToken,
    required this.targetIndex,
    required this.onSpin,
    required this.onSettled,
  });

  final SpinStatus status;
  final bool spinning;
  final int spinToken;
  final int? targetIndex;
  final VoidCallback onSpin;
  final VoidCallback onSettled;

  @override
  Widget build(BuildContext context) {
    final s = RewardsStrings.of(context);

    if (status.segments.isEmpty) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: [
          Padding(
            padding: const EdgeInsets.only(top: AppSpacing.xxxl),
            child: EmptyView(icon: Icons.casino_outlined, title: s.noSpins, message: s.noSpinsBody),
          ),
        ],
      );
    }

    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(AppSpacing.screen),
      children: [
        Text(s.spinSubtitle, textAlign: TextAlign.center, style: context.textTheme.titleMedium),
        const SizedBox(height: AppSpacing.xl),
        Center(
          child: SpinWheelWidget(
            segments: status.segments,
            spinToken: spinToken,
            targetIndex: targetIndex,
            onSettled: onSettled,
          ),
        ),
        const SizedBox(height: AppSpacing.xl),
        Text(
          s.spinsLeft(status.spinsRemaining),
          textAlign: TextAlign.center,
          style: context.textTheme.titleMedium?.copyWith(color: context.colors.primary),
        ),
        const SizedBox(height: AppSpacing.md),
        FilledButton.icon(
          onPressed: (spinning || !status.canSpin) ? null : onSpin,
          icon: spinning
              ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2))
              : const Icon(Icons.casino),
          label: Text(spinning ? s.spinning : s.spinNow),
        ),
        if (!status.canSpin && status.spinsRemaining == 0) ...[
          const SizedBox(height: AppSpacing.md),
          Text(
            s.noSpinsBody,
            textAlign: TextAlign.center,
            style: context.textTheme.bodySmall?.copyWith(color: context.colors.onSurfaceVariant),
          ),
        ],
      ],
    );
  }
}

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/error/app_exception.dart';
import '../../../core/responsive/responsive.dart';
import '../../../core/theme/app_radius.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../shared/extensions/context_extensions.dart';
import '../../../shared/widgets/app_scaffold.dart';
import '../application/checkin_controller.dart';
import '../l10n/rewards_strings.dart';
import '../providers/rewards_providers.dart';
import '../resources/reward_visuals.dart';
import 'widgets/checkin_calendar.dart';
import 'widgets/reward_error.dart';
import 'widgets/reward_result_dialog.dart';
import 'widgets/reward_skeleton.dart';

/// Daily check-in: streak, claimable reward, and the reward ladder.
class DailyCheckinScreen extends ConsumerStatefulWidget {
  const DailyCheckinScreen({super.key});

  @override
  ConsumerState<DailyCheckinScreen> createState() => _DailyCheckinScreenState();
}

class _DailyCheckinScreenState extends ConsumerState<DailyCheckinScreen> {
  bool _claiming = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(checkinControllerProvider.notifier).load();
    });
  }

  Future<void> _claim() async {
    setState(() => _claiming = true);
    try {
      final result = await ref.read(checkinControllerProvider.notifier).claim();
      if (mounted) {
        await showRewardResult(context, coins: result.coinsAwarded);
      }
    } on AppException catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context)
          ..hideCurrentSnackBar()
          ..showSnackBar(SnackBar(content: Text(e.message)));
      }
    } finally {
      if (mounted) {
        setState(() => _claiming = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = RewardsStrings.of(context);
    final state = ref.watch(checkinControllerProvider);

    return AppScaffold(
      appBar: AppBar(title: Text(s.dailyCheckin)),
      body: RefreshIndicator(
        onRefresh: () => ref.read(checkinControllerProvider.notifier).refresh(),
        child: switch (state) {
          AsyncData(:final value) => _Body(
              data: value,
              claiming: _claiming,
              onClaim: _claim,
            ),
          AsyncError(:final error) => RewardErrorView(
              error: error,
              onRetry: () => ref.read(checkinControllerProvider.notifier).load(),
            ),
          _ => const RewardListSkeleton(itemCount: 3, itemHeight: 120),
        },
      ),
    );
  }
}

class _Body extends StatelessWidget {
  const _Body({required this.data, required this.claiming, required this.onClaim});

  final CheckinData data;
  final bool claiming;
  final VoidCallback onClaim;

  @override
  Widget build(BuildContext context) {
    final s = RewardsStrings.of(context);
    final localeCode = Localizations.localeOf(context).languageCode;
    final status = data.status;

    final content = ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(AppSpacing.screen),
      children: [
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(AppSpacing.xl),
          decoration: BoxDecoration(
            borderRadius: AppRadius.lgAll,
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [context.colors.primary, context.colors.primaryContainer],
            ),
          ),
          child: Column(
            children: [
              Icon(Icons.local_fire_department, size: 48, color: context.colors.onPrimary),
              const SizedBox(height: AppSpacing.sm),
              Text(
                s.streak(status.currentStreak),
                style: context.textTheme.headlineSmall
                    ?.copyWith(color: context.colors.onPrimary, fontWeight: FontWeight.w800),
              ),
              const SizedBox(height: AppSpacing.xs),
              Text(
                s.checkinSubtitle,
                textAlign: TextAlign.center,
                style: context.textTheme.bodyMedium?.copyWith(color: context.colors.onPrimary),
              ),
              const SizedBox(height: AppSpacing.lg),
              if (status.canClaimToday)
                _ClaimButton(
                  label: '${s.claimNow} · ${RewardVisuals.coins(status.nextRewardCoins, localeCode: localeCode)}',
                  claiming: claiming,
                  onClaim: onClaim,
                )
              else
                Column(
                  children: [
                    Icon(Icons.check_circle, color: context.colors.onPrimary, size: 32),
                    const SizedBox(height: AppSpacing.xs),
                    Text(
                      s.claimedToday,
                      style: context.textTheme.titleMedium?.copyWith(color: context.colors.onPrimary),
                    ),
                    Text(
                      s.comeBackTomorrow,
                      textAlign: TextAlign.center,
                      style: context.textTheme.bodySmall?.copyWith(color: context.colors.onPrimary),
                    ),
                  ],
                ),
            ],
          ),
        ),
        const SizedBox(height: AppSpacing.xl),
        CheckinCalendarView(calendar: data.calendar, currentStreak: status.currentStreak),
      ],
    );

    return ResponsiveLayout(
      phone: (context) => content,
      tablet: (context) => Center(
        child: ConstrainedBox(constraints: const BoxConstraints(maxWidth: 640), child: content),
      ),
    );
  }
}

class _ClaimButton extends StatelessWidget {
  const _ClaimButton({required this.label, required this.claiming, required this.onClaim});

  final String label;
  final bool claiming;
  final VoidCallback onClaim;

  @override
  Widget build(BuildContext context) {
    final s = RewardsStrings.of(context);
    return SizedBox(
      width: double.infinity,
      child: FilledButton.tonalIcon(
        onPressed: claiming ? null : onClaim,
        icon: claiming
            ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2))
            : const Icon(Icons.redeem),
        label: Text(claiming ? s.claiming : label),
      ),
    );
  }
}

import 'package:flutter/material.dart';

import '../../../core/responsive/responsive.dart';
import '../../../core/theme/app_radius.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../shared/extensions/context_extensions.dart';
import '../../../shared/widgets/app_scaffold.dart';
import '../l10n/rewards_strings.dart';
import 'daily_checkin_screen.dart';
import 'reward_history_screen.dart';
import 'scratch_card_screen.dart';
import 'spin_wheel_screen.dart';
import 'tasks_screen.dart';

/// Rewards hub: entry points to check-in, scratch, spin, tasks, and history.
class RewardsScreen extends StatelessWidget {
  const RewardsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final s = RewardsStrings.of(context);

    final entries = <_RewardEntryData>[
      _RewardEntryData(
        icon: Icons.event_available,
        title: s.dailyCheckin,
        color: context.colors.primary,
        builder: (_) => const DailyCheckinScreen(),
      ),
      _RewardEntryData(
        icon: Icons.card_giftcard,
        title: s.scratchCards,
        color: context.colors.tertiary,
        builder: (_) => const ScratchCardScreen(),
      ),
      _RewardEntryData(
        icon: Icons.casino,
        title: s.spinWheel,
        color: context.colors.secondary,
        builder: (_) => const SpinWheelScreen(),
      ),
      _RewardEntryData(
        icon: Icons.checklist,
        title: s.tasks,
        color: context.colors.primary,
        builder: (_) => const TasksScreen(),
      ),
      _RewardEntryData(
        icon: Icons.history,
        title: s.rewardHistory,
        color: context.colors.secondary,
        builder: (_) => const RewardHistoryScreen(),
      ),
    ];

    return AppScaffold(
      appBar: AppBar(title: Text(s.title)),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(AppSpacing.screen),
          children: [
            Text(s.subtitle, style: context.textTheme.titleMedium),
            const SizedBox(height: AppSpacing.lg),
            LayoutBuilder(
              builder: (context, constraints) {
                final columns = constraints.maxWidth >= Breakpoints.tablet ? 3 : 2;
                return GridView.count(
                  crossAxisCount: columns,
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  mainAxisSpacing: AppSpacing.md,
                  crossAxisSpacing: AppSpacing.md,
                  childAspectRatio: 1.1,
                  children: [for (final e in entries) _RewardEntryCard(data: e)],
                );
              },
            ),
          ],
        ),
      ),
    );
  }
}

class _RewardEntryData {
  const _RewardEntryData({
    required this.icon,
    required this.title,
    required this.color,
    required this.builder,
  });

  final IconData icon;
  final String title;
  final Color color;
  final WidgetBuilder builder;
}

class _RewardEntryCard extends StatelessWidget {
  const _RewardEntryCard({required this.data});

  final _RewardEntryData data;

  @override
  Widget build(BuildContext context) {
    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: () => Navigator.of(context).push(
          MaterialPageRoute<void>(builder: data.builder),
        ),
        child: Padding(
          padding: const EdgeInsets.all(AppSpacing.lg),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                width: 56,
                height: 56,
                decoration: BoxDecoration(
                  color: data.color.withValues(alpha: 0.16),
                  borderRadius: AppRadius.lgAll,
                ),
                child: Icon(data.icon, color: data.color, size: 28),
              ),
              const SizedBox(height: AppSpacing.md),
              Text(
                data.title,
                textAlign: TextAlign.center,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: context.textTheme.titleSmall,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

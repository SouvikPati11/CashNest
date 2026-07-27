import 'package:flutter/material.dart';

import '../../../../core/theme/app_radius.dart';
import '../../../../core/theme/app_spacing.dart';
import '../../../../shared/extensions/context_extensions.dart';
import '../../l10n/rewards_strings.dart';
import '../../models/checkin_calendar.dart';
import '../../resources/reward_visuals.dart';

/// The reward ladder: a horizontal strip of day rungs with the streak progress
/// filled up to [currentStreak].
class CheckinCalendarView extends StatelessWidget {
  const CheckinCalendarView({
    required this.calendar,
    required this.currentStreak,
    super.key,
  });

  final CheckinCalendar calendar;
  final int currentStreak;

  @override
  Widget build(BuildContext context) {
    final s = RewardsStrings.of(context);
    if (calendar.ladder.isEmpty) {
      return const SizedBox.shrink();
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(s.rewardLadder, style: context.textTheme.titleMedium),
        const SizedBox(height: AppSpacing.md),
        SizedBox(
          height: 104,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            itemCount: calendar.ladder.length,
            separatorBuilder: (_, __) => const SizedBox(width: AppSpacing.sm),
            itemBuilder: (context, index) {
              final rung = calendar.ladder[index];
              return _LadderRung(
                day: rung.day,
                coins: rung.coins,
                isMilestone: rung.isMilestone,
                isClaimed: rung.day <= currentStreak,
                isNext: rung.day == currentStreak + 1,
              );
            },
          ),
        ),
      ],
    );
  }
}

class _LadderRung extends StatelessWidget {
  const _LadderRung({
    required this.day,
    required this.coins,
    required this.isMilestone,
    required this.isClaimed,
    required this.isNext,
  });

  final int day;
  final int coins;
  final bool isMilestone;
  final bool isClaimed;
  final bool isNext;

  @override
  Widget build(BuildContext context) {
    final s = RewardsStrings.of(context);
    final scheme = context.colors;
    final localeCode = Localizations.localeOf(context).languageCode;

    final Color bg;
    final Color fg;
    if (isClaimed) {
      bg = scheme.primary;
      fg = scheme.onPrimary;
    } else if (isNext) {
      bg = scheme.primaryContainer;
      fg = scheme.onPrimaryContainer;
    } else {
      bg = scheme.surfaceContainerHighest;
      fg = scheme.onSurfaceVariant;
    }

    return Container(
      width: 76,
      padding: const EdgeInsets.symmetric(vertical: AppSpacing.sm, horizontal: AppSpacing.xs),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: AppRadius.mdAll,
        border: isNext
            ? Border.all(color: scheme.primary, width: 2)
            : null,
      ),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Text(s.dayLabel(day), style: context.textTheme.labelSmall?.copyWith(color: fg)),
          const SizedBox(height: AppSpacing.xs),
          Icon(
            isClaimed
                ? Icons.check_circle
                : (isMilestone ? Icons.workspace_premium : Icons.monetization_on_outlined),
            color: fg,
            size: 24,
          ),
          const SizedBox(height: AppSpacing.xs),
          Text(
            RewardVisuals.coins(coins, localeCode: localeCode),
            style: context.textTheme.labelMedium?.copyWith(color: fg, fontWeight: FontWeight.w700),
          ),
        ],
      ),
    );
  }
}

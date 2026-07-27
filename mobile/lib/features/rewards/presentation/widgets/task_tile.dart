import 'package:flutter/material.dart';

import '../../../../core/theme/app_radius.dart';
import '../../../../core/theme/app_spacing.dart';
import '../../../../shared/extensions/context_extensions.dart';
import '../../l10n/rewards_strings.dart';
import '../../models/reward_task.dart';
import '../../resources/reward_visuals.dart';

/// A task row with reward, per-user progress, and a start/complete action.
class TaskTile extends StatelessWidget {
  const TaskTile({
    required this.task,
    required this.busy,
    this.onStart,
    this.onComplete,
    super.key,
  });

  final RewardTask task;
  final bool busy;
  final VoidCallback? onStart;
  final VoidCallback? onComplete;

  @override
  Widget build(BuildContext context) {
    final s = RewardsStrings.of(context);
    final localeCode = Localizations.localeOf(context).languageCode;
    final done = !task.isAvailable;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.lg),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  width: 44,
                  height: 44,
                  decoration: BoxDecoration(
                    color: context.colors.secondaryContainer,
                    borderRadius: AppRadius.mdAll,
                  ),
                  child: Icon(Icons.task_alt, color: context.colors.onSecondaryContainer),
                ),
                const SizedBox(width: AppSpacing.md),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(task.title, style: context.textTheme.titleMedium),
                      const SizedBox(height: AppSpacing.xxs),
                      Row(
                        children: [
                          Icon(Icons.monetization_on, size: 16, color: context.colors.primary),
                          const SizedBox(width: AppSpacing.xxs),
                          Text(
                            '${RewardVisuals.coins(task.rewardCoins, localeCode: localeCode)} ${s.coins}',
                            style: context.textTheme.labelLarge
                                ?.copyWith(color: context.colors.primary, fontWeight: FontWeight.w700),
                          ),
                          if (task.perUserLimit > 1) ...[
                            const SizedBox(width: AppSpacing.sm),
                            Text(
                              '${task.myCompletions}/${task.perUserLimit}',
                              style: context.textTheme.labelMedium
                                  ?.copyWith(color: context.colors.onSurfaceVariant),
                            ),
                          ],
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
            if (task.description != null && task.description!.isNotEmpty) ...[
              const SizedBox(height: AppSpacing.sm),
              Text(
                task.description!,
                style: context.textTheme.bodySmall?.copyWith(color: context.colors.onSurfaceVariant),
              ),
            ],
            const SizedBox(height: AppSpacing.md),
            Row(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                if (done)
                  Chip(
                    avatar: Icon(Icons.check, size: 18, color: context.colors.onSecondaryContainer),
                    label: Text(s.completed),
                    backgroundColor: context.colors.secondaryContainer,
                  )
                else if (busy)
                  const SizedBox(
                    width: 24,
                    height: 24,
                    child: CircularProgressIndicator(strokeWidth: 2.5),
                  )
                else ...[
                  if (onStart != null)
                    OutlinedButton(onPressed: onStart, child: Text(s.start)),
                  if (onStart != null && onComplete != null)
                    const SizedBox(width: AppSpacing.sm),
                  if (onComplete != null)
                    FilledButton(onPressed: onComplete, child: Text(s.complete)),
                ],
              ],
            ),
          ],
        ),
      ),
    );
  }
}

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_radius.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../shared/extensions/context_extensions.dart';
import '../../../shared/widgets/app_scaffold.dart';
import '../../../shared/widgets/empty_view.dart';
import '../l10n/rewards_strings.dart';
import '../models/reward_history_entry.dart';
import '../providers/rewards_providers.dart';
import 'widgets/reward_error.dart';
import 'widgets/reward_skeleton.dart';
import '../resources/reward_visuals.dart';

/// Reward history: scratch, spin, and task rewards merged by recency.
class RewardHistoryScreen extends ConsumerStatefulWidget {
  const RewardHistoryScreen({super.key});

  @override
  ConsumerState<RewardHistoryScreen> createState() => _RewardHistoryScreenState();
}

class _RewardHistoryScreenState extends ConsumerState<RewardHistoryScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(rewardHistoryControllerProvider.notifier).load();
    });
  }

  @override
  Widget build(BuildContext context) {
    final s = RewardsStrings.of(context);
    final state = ref.watch(rewardHistoryControllerProvider);

    return AppScaffold(
      appBar: AppBar(title: Text(s.rewardHistory)),
      body: RefreshIndicator(
        onRefresh: () => ref.read(rewardHistoryControllerProvider.notifier).refresh(),
        child: switch (state) {
          AsyncData(:final value) => value.isEmpty ? _empty(s) : _list(value),
          AsyncError(:final error) => RewardErrorView(
              error: error,
              onRetry: () => ref.read(rewardHistoryControllerProvider.notifier).load(),
            ),
          _ => const RewardListSkeleton(itemHeight: 72),
        },
      ),
    );
  }

  Widget _empty(RewardsStrings s) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        Padding(
          padding: const EdgeInsets.only(top: AppSpacing.xxxl),
          child: EmptyView(icon: Icons.history, title: s.noHistory, message: s.noHistoryBody),
        ),
      ],
    );
  }

  Widget _list(List<RewardHistoryEntry> entries) {
    return ListView.separated(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(AppSpacing.screen),
      itemCount: entries.length,
      separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.sm),
      itemBuilder: (context, index) => _HistoryTile(entry: entries[index]),
    );
  }
}

class _HistoryTile extends StatelessWidget {
  const _HistoryTile({required this.entry});

  final RewardHistoryEntry entry;

  IconData get _icon => switch (entry.kind) {
        RewardKind.checkin => Icons.event_available,
        RewardKind.scratch => Icons.card_giftcard,
        RewardKind.spin => Icons.casino,
        RewardKind.task => Icons.task_alt,
      };

  String _kindKey() => switch (entry.kind) {
        RewardKind.checkin => 'checkin',
        RewardKind.scratch => 'scratch',
        RewardKind.spin => 'spin',
        RewardKind.task => 'task',
      };

  @override
  Widget build(BuildContext context) {
    final s = RewardsStrings.of(context);
    final localeCode = Localizations.localeOf(context).languageCode;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.md),
        child: Row(
          children: [
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                color: context.colors.primaryContainer,
                borderRadius: AppRadius.smAll,
              ),
              child: Icon(_icon, size: 20, color: context.colors.onPrimaryContainer),
            ),
            const SizedBox(width: AppSpacing.md),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(s.kindLabel(_kindKey()), style: context.textTheme.bodyLarge),
                  if (entry.createdAt != null)
                    Text(
                      RewardVisuals.dateTime(entry.createdAt!),
                      style: context.textTheme.bodySmall
                          ?.copyWith(color: context.colors.onSurfaceVariant),
                    ),
                ],
              ),
            ),
            if (entry.coins > 0)
              Text(
                '+${RewardVisuals.coins(entry.coins, localeCode: localeCode)}',
                style: context.textTheme.titleMedium?.copyWith(
                  color: Colors.green.shade600,
                  fontWeight: FontWeight.w700,
                ),
              ),
          ],
        ),
      ),
    );
  }
}

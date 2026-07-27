import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/error/app_exception.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../shared/widgets/app_scaffold.dart';
import '../../../shared/widgets/empty_view.dart';
import '../l10n/rewards_strings.dart';
import '../models/reward_task.dart';
import '../providers/rewards_providers.dart';
import 'widgets/reward_error.dart';
import 'widgets/reward_result_dialog.dart';
import 'widgets/reward_skeleton.dart';
import 'widgets/task_tile.dart';

/// Tasks: list of earnable tasks with a start → complete flow.
class TasksScreen extends ConsumerStatefulWidget {
  const TasksScreen({super.key});

  @override
  ConsumerState<TasksScreen> createState() => _TasksScreenState();
}

class _TasksScreenState extends ConsumerState<TasksScreen> {
  String? _busyUuid;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(tasksControllerProvider.notifier).load();
    });
  }

  void _snack(String message) {
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text(message)));
  }

  Future<void> _start(RewardTask task) async {
    if (_busyUuid != null) {
      return;
    }
    setState(() => _busyUuid = task.uuid);
    try {
      await ref.read(tasksControllerProvider.notifier).start(task.uuid);
      if (mounted) {
        _snack(RewardsStrings.of(context).start);
      }
    } on AppException catch (e) {
      if (mounted) {
        _snack(e.message);
      }
    } finally {
      if (mounted) {
        setState(() => _busyUuid = null);
      }
    }
  }

  Future<void> _complete(RewardTask task) async {
    if (_busyUuid != null) {
      return;
    }
    setState(() => _busyUuid = task.uuid);
    try {
      final result = await ref.read(tasksControllerProvider.notifier).complete(task.uuid);
      if (!mounted) {
        return;
      }
      if (result.isCredited) {
        await showRewardResult(context, coins: result.coinsAwarded ?? task.rewardCoins);
      } else {
        _snack(RewardsStrings.of(context).taskPending);
      }
    } on AppException catch (e) {
      if (mounted) {
        _snack(e.message);
      }
    } finally {
      if (mounted) {
        setState(() => _busyUuid = null);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = RewardsStrings.of(context);
    final state = ref.watch(tasksControllerProvider);

    return AppScaffold(
      appBar: AppBar(title: Text(s.tasks)),
      body: RefreshIndicator(
        onRefresh: () => ref.read(tasksControllerProvider.notifier).refresh(),
        child: switch (state) {
          AsyncData(:final value) => value.isEmpty ? _empty(s) : _list(value),
          AsyncError(:final error) => RewardErrorView(
              error: error,
              onRetry: () => ref.read(tasksControllerProvider.notifier).load(),
            ),
          _ => const RewardListSkeleton(),
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
          child: EmptyView(icon: Icons.checklist_outlined, title: s.noTasks, message: s.noTasksBody),
        ),
      ],
    );
  }

  Widget _list(List<RewardTask> tasks) {
    return ListView.separated(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(AppSpacing.screen),
      itemCount: tasks.length,
      separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.md),
      itemBuilder: (context, index) {
        final task = tasks[index];
        final available = task.isAvailable;
        return TaskTile(
          task: task,
          busy: _busyUuid == task.uuid,
          onStart: available ? () => _start(task) : null,
          onComplete: available ? () => _complete(task) : null,
        );
      },
    );
  }
}

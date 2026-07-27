import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_result.dart';
import '../data/rewards_repository.dart';
import '../models/reward_task.dart';
import '../models/task_action_result.dart';

/// Loads earnable tasks and drives the start → complete flow.
class TasksController extends StateNotifier<AsyncValue<List<RewardTask>>> {
  TasksController(this._repository) : super(const AsyncValue.loading());

  final RewardsRepository _repository;

  Future<void> load() async {
    state = const AsyncValue.loading();
    state = await AsyncValue.guard(_fetch);
  }

  Future<void> refresh() async {
    state = await AsyncValue.guard(_fetch);
  }

  Future<List<RewardTask>> _fetch() async {
    final result = await _repository.fetchTasks();
    return switch (result) {
      ApiSuccess(:final data) => data,
      ApiFailure(:final error) => throw error,
    };
  }

  Future<TaskActionResult> start(String uuid) async {
    final result = await _repository.startTask(uuid);
    return switch (result) {
      ApiSuccess(:final data) => data,
      ApiFailure(:final error) => throw error,
    };
  }

  /// Complete a task; on `credited` the task's completion count is bumped so the
  /// UI reflects the new availability. Returns the result or throws.
  Future<TaskActionResult> complete(String uuid, {String? verificationRef}) async {
    final result = await _repository.completeTask(uuid, verificationRef: verificationRef);
    return switch (result) {
      ApiSuccess(:final data) => _onCompleted(uuid, data),
      ApiFailure(:final error) => throw error,
    };
  }

  TaskActionResult _onCompleted(String uuid, TaskActionResult result) {
    if (result.isCredited) {
      final current = state.valueOrNull;
      if (current != null) {
        state = AsyncValue.data([
          for (final t in current)
            if (t.uuid == uuid) t.copyWith(myCompletions: t.myCompletions + 1) else t,
        ]);
      }
    }
    return result;
  }
}

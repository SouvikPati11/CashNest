import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../app/di/providers.dart';
import '../application/checkin_controller.dart';
import '../application/reward_history_controller.dart';
import '../application/scratch_controller.dart';
import '../application/spin_controller.dart';
import '../application/tasks_controller.dart';
import '../data/rewards_repository.dart';
import '../data/rewards_repository_impl.dart';
import '../models/reward_history_entry.dart';
import '../models/reward_task.dart';
import '../models/scratch_card.dart';
import '../models/spin_status.dart';

/// Riverpod wiring for the rewards module. Reuses the foundation API client; the
/// foundation, auth, home, and wallet modules are not modified.

final rewardsRepositoryProvider = Provider<RewardsRepository>(
  (ref) => RewardsRepositoryImpl(ref.watch(apiClientProvider)),
);

final checkinControllerProvider =
    StateNotifierProvider<CheckinController, AsyncValue<CheckinData>>(
  (ref) => CheckinController(ref.watch(rewardsRepositoryProvider)),
);

final scratchControllerProvider =
    StateNotifierProvider<ScratchController, AsyncValue<List<ScratchCard>>>(
  (ref) => ScratchController(ref.watch(rewardsRepositoryProvider)),
);

final spinControllerProvider =
    StateNotifierProvider<SpinController, AsyncValue<SpinStatus>>(
  (ref) => SpinController(ref.watch(rewardsRepositoryProvider)),
);

final tasksControllerProvider =
    StateNotifierProvider<TasksController, AsyncValue<List<RewardTask>>>(
  (ref) => TasksController(ref.watch(rewardsRepositoryProvider)),
);

final rewardHistoryControllerProvider =
    StateNotifierProvider<RewardHistoryController, AsyncValue<List<RewardHistoryEntry>>>(
  (ref) => RewardHistoryController(ref.watch(rewardsRepositoryProvider)),
);

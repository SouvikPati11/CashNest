import '../../../core/network/api_result.dart';
import '../models/checkin_calendar.dart';
import '../models/checkin_status.dart';
import '../models/claim_result.dart';
import '../models/reward_history_entry.dart';
import '../models/reward_task.dart';
import '../models/scratch_card.dart';
import '../models/spin_result.dart';
import '../models/spin_status.dart';
import '../models/task_action_result.dart';

/// Contract for the rewards module: daily check-in, scratch cards, spin wheel,
/// tasks, and merged reward history.
abstract interface class RewardsRepository {
  // Daily check-in
  Future<ApiResult<CheckinStatus>> fetchCheckinStatus();
  Future<ApiResult<CheckinCalendar>> fetchCheckinCalendar();
  Future<ApiResult<ClaimResult>> claimCheckin();

  // Scratch cards
  Future<ApiResult<List<ScratchCard>>> fetchAvailableScratchCards();
  Future<ApiResult<ScratchCard>> revealScratchCard(String uuid);
  Future<ApiResult<ClaimResult>> claimScratchCard(String uuid);
  Future<ApiResult<List<RewardHistoryEntry>>> fetchScratchHistory();

  // Spin wheel
  Future<ApiResult<SpinStatus>> fetchSpinStatus();
  Future<ApiResult<SpinResult>> spin({String source = 'free'});
  Future<ApiResult<List<RewardHistoryEntry>>> fetchSpinHistory();

  // Tasks
  Future<ApiResult<List<RewardTask>>> fetchTasks();
  Future<ApiResult<RewardTask>> fetchTask(String uuid);
  Future<ApiResult<TaskActionResult>> startTask(String uuid);
  Future<ApiResult<TaskActionResult>> completeTask(String uuid, {String? verificationRef});
  Future<ApiResult<List<RewardHistoryEntry>>> fetchTaskHistory();
}

import 'package:cashnest/core/network/api_result.dart';
import 'package:cashnest/features/rewards/data/rewards_repository.dart';
import 'package:cashnest/features/rewards/models/checkin_calendar.dart';
import 'package:cashnest/features/rewards/models/checkin_status.dart';
import 'package:cashnest/features/rewards/models/claim_result.dart';
import 'package:cashnest/features/rewards/models/reward_history_entry.dart';
import 'package:cashnest/features/rewards/models/reward_task.dart';
import 'package:cashnest/features/rewards/models/scratch_card.dart';
import 'package:cashnest/features/rewards/models/spin_result.dart';
import 'package:cashnest/features/rewards/models/spin_status.dart';
import 'package:cashnest/features/rewards/models/task_action_result.dart';

/// A configurable fake [RewardsRepository] for controller tests.
class FakeRewardsRepository implements RewardsRepository {
  ApiResult<CheckinStatus> checkinStatus =
      const ApiResult.success(CheckinStatus(canClaimToday: true, currentStreak: 3, nextRewardCoins: 30));
  ApiResult<CheckinCalendar> checkinCalendar =
      const ApiResult.success(CheckinCalendar(ladder: [], currentStreak: 3));
  ApiResult<ClaimResult> checkinClaim =
      const ApiResult.success(ClaimResult(coinsAwarded: 30, streakDay: 4, newBalance: 4230));

  ApiResult<List<ScratchCard>> availableCards = const ApiResult.success([
    ScratchCard(uuid: 'sc_1', status: 'issued'),
    ScratchCard(uuid: 'sc_2', status: 'issued'),
  ]);
  ApiResult<ScratchCard> revealResult =
      const ApiResult.success(ScratchCard(uuid: 'sc_1', status: 'revealed', rewardCoins: 50));
  ApiResult<ClaimResult> scratchClaim =
      const ApiResult.success(ClaimResult(coinsAwarded: 50, newBalance: 4280));
  ApiResult<List<RewardHistoryEntry>> scratchHistory = const ApiResult.success([]);

  ApiResult<SpinStatus> spinStatus = const ApiResult.success(SpinStatus(
    spinsRemaining: 2,
    dailyLimit: 3,
    segments: [
      SpinSegment(id: 'seg_1', label: '50', colorHex: '#FFCC00', position: 0),
      SpinSegment(id: 'seg_2', label: '20', colorHex: '#00FF00', position: 1),
    ],
  ));
  ApiResult<SpinResult> spinResult =
      const ApiResult.success(SpinResult(segmentId: 'seg_1', rewardType: 'coins', rewardCoins: 50, spinsRemaining: 1));
  ApiResult<List<RewardHistoryEntry>> spinHistory = const ApiResult.success([]);

  ApiResult<List<RewardTask>> tasks = const ApiResult.success([
    RewardTask(uuid: 't_1', title: 'Watch', rewardCoins: 100, perUserLimit: 1),
  ]);
  ApiResult<RewardTask> task =
      const ApiResult.success(RewardTask(uuid: 't_1', title: 'Watch', rewardCoins: 100));
  ApiResult<TaskActionResult> startResult =
      const ApiResult.success(TaskActionResult(status: 'started', completionUuid: 'tc_1'));
  ApiResult<TaskActionResult> completeResult =
      const ApiResult.success(TaskActionResult(status: 'credited', coinsAwarded: 100, newBalance: 4430));
  ApiResult<List<RewardHistoryEntry>> taskHistory = const ApiResult.success([]);

  @override
  Future<ApiResult<CheckinStatus>> fetchCheckinStatus() async => checkinStatus;

  @override
  Future<ApiResult<CheckinCalendar>> fetchCheckinCalendar() async => checkinCalendar;

  @override
  Future<ApiResult<ClaimResult>> claimCheckin() async => checkinClaim;

  @override
  Future<ApiResult<List<ScratchCard>>> fetchAvailableScratchCards() async => availableCards;

  @override
  Future<ApiResult<ScratchCard>> revealScratchCard(String uuid) async => revealResult;

  @override
  Future<ApiResult<ClaimResult>> claimScratchCard(String uuid) async => scratchClaim;

  @override
  Future<ApiResult<List<RewardHistoryEntry>>> fetchScratchHistory() async => scratchHistory;

  @override
  Future<ApiResult<SpinStatus>> fetchSpinStatus() async => spinStatus;

  @override
  Future<ApiResult<SpinResult>> spin({String source = 'free'}) async => spinResult;

  @override
  Future<ApiResult<List<RewardHistoryEntry>>> fetchSpinHistory() async => spinHistory;

  @override
  Future<ApiResult<List<RewardTask>>> fetchTasks() async => tasks;

  @override
  Future<ApiResult<RewardTask>> fetchTask(String uuid) async => task;

  @override
  Future<ApiResult<TaskActionResult>> startTask(String uuid) async => startResult;

  @override
  Future<ApiResult<TaskActionResult>> completeTask(String uuid, {String? verificationRef}) async =>
      completeResult;

  @override
  Future<ApiResult<List<RewardHistoryEntry>>> fetchTaskHistory() async => taskHistory;
}

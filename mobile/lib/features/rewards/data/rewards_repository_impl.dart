import 'package:dio/dio.dart';

import '../../../core/network/api_client.dart';
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
import '../services/idempotency_key.dart';
import 'rewards_repository.dart';

/// [RewardsRepository] over the foundation [ApiClient]. Reward-crediting
/// mutations send an `X-Idempotency-Key` so retries don't double-submit.
class RewardsRepositoryImpl implements RewardsRepository {
  RewardsRepositoryImpl(this._client);

  final ApiClient _client;

  static Map<String, dynamic> _map(dynamic data) =>
      data is Map<String, dynamic> ? data : const <String, dynamic>{};

  static List<T> _list<T>(dynamic data, T Function(Map<String, dynamic>) fromJson) {
    if (data is! List) {
      return <T>[];
    }
    return data.whereType<Map<String, dynamic>>().map(fromJson).toList(growable: false);
  }

  Options _idempotent() =>
      Options(headers: {'X-Idempotency-Key': IdempotencyKey.generate()});

  // ── Daily check-in ─────────────────────────────────────────────────────────

  @override
  Future<ApiResult<CheckinStatus>> fetchCheckinStatus() {
    return _client.get<CheckinStatus>(
      '/checkin/status',
      decoder: (data) => CheckinStatus.fromJson(_map(data)),
    );
  }

  @override
  Future<ApiResult<CheckinCalendar>> fetchCheckinCalendar() {
    return _client.get<CheckinCalendar>(
      '/checkin/calendar',
      decoder: (data) => CheckinCalendar.fromJson(_map(data)),
    );
  }

  @override
  Future<ApiResult<ClaimResult>> claimCheckin() {
    return _client.post<ClaimResult>(
      '/checkin/claim',
      options: _idempotent(),
      decoder: (data) => ClaimResult.fromJson(_map(data)),
    );
  }

  // ── Scratch cards ──────────────────────────────────────────────────────────

  @override
  Future<ApiResult<List<ScratchCard>>> fetchAvailableScratchCards() {
    return _client.get<List<ScratchCard>>(
      '/scratch/available',
      decoder: (data) => _list(data, ScratchCard.fromJson),
    );
  }

  @override
  Future<ApiResult<ScratchCard>> revealScratchCard(String uuid) {
    return _client.post<ScratchCard>(
      '/scratch/$uuid/reveal',
      options: _idempotent(),
      decoder: (data) => ScratchCard.fromJson(_map(data)),
    );
  }

  @override
  Future<ApiResult<ClaimResult>> claimScratchCard(String uuid) {
    return _client.post<ClaimResult>(
      '/scratch/$uuid/claim',
      options: _idempotent(),
      decoder: (data) => ClaimResult.fromJson(_map(data)),
    );
  }

  @override
  Future<ApiResult<List<RewardHistoryEntry>>> fetchScratchHistory() {
    return _client.get<List<RewardHistoryEntry>>(
      '/scratch/history',
      decoder: (data) => _list(data, RewardHistoryEntry.scratch),
    );
  }

  // ── Spin wheel ─────────────────────────────────────────────────────────────

  @override
  Future<ApiResult<SpinStatus>> fetchSpinStatus() {
    return _client.get<SpinStatus>(
      '/spin/status',
      decoder: (data) => SpinStatus.fromJson(_map(data)),
    );
  }

  @override
  Future<ApiResult<SpinResult>> spin({String source = 'free'}) {
    return _client.post<SpinResult>(
      '/spin',
      body: {'source': source},
      options: _idempotent(),
      decoder: (data) => SpinResult.fromJson(_map(data)),
    );
  }

  @override
  Future<ApiResult<List<RewardHistoryEntry>>> fetchSpinHistory() {
    return _client.get<List<RewardHistoryEntry>>(
      '/spin/history',
      decoder: (data) => _list(data, RewardHistoryEntry.spin),
    );
  }

  // ── Tasks ──────────────────────────────────────────────────────────────────

  @override
  Future<ApiResult<List<RewardTask>>> fetchTasks() {
    return _client.get<List<RewardTask>>(
      '/tasks',
      decoder: (data) => _list(data, RewardTask.fromJson),
    );
  }

  @override
  Future<ApiResult<RewardTask>> fetchTask(String uuid) {
    return _client.get<RewardTask>(
      '/tasks/$uuid',
      decoder: (data) => RewardTask.fromJson(_map(data)),
    );
  }

  @override
  Future<ApiResult<TaskActionResult>> startTask(String uuid) {
    return _client.post<TaskActionResult>(
      '/tasks/$uuid/start',
      decoder: (data) => TaskActionResult.fromJson(_map(data)),
    );
  }

  @override
  Future<ApiResult<TaskActionResult>> completeTask(String uuid, {String? verificationRef}) {
    return _client.post<TaskActionResult>(
      '/tasks/$uuid/complete',
      body: {if (verificationRef != null) 'verification_ref': verificationRef},
      options: _idempotent(),
      decoder: (data) => TaskActionResult.fromJson(_map(data)),
    );
  }

  @override
  Future<ApiResult<List<RewardHistoryEntry>>> fetchTaskHistory() {
    return _client.get<List<RewardHistoryEntry>>(
      '/tasks/history',
      decoder: (data) => _list(data, RewardHistoryEntry.task),
    );
  }
}

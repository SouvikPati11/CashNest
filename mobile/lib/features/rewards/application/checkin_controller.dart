import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_result.dart';
import '../data/rewards_repository.dart';
import '../models/checkin_calendar.dart';
import '../models/checkin_status.dart';
import '../models/claim_result.dart';

/// Daily check-in status + reward ladder.
@immutable
class CheckinData {
  const CheckinData({required this.status, required this.calendar});

  final CheckinStatus status;
  final CheckinCalendar calendar;

  CheckinData copyWith({CheckinStatus? status, CheckinCalendar? calendar}) {
    return CheckinData(
      status: status ?? this.status,
      calendar: calendar ?? this.calendar,
    );
  }
}

/// Loads the check-in status/calendar and performs the daily claim.
class CheckinController extends StateNotifier<AsyncValue<CheckinData>> {
  CheckinController(this._repository) : super(const AsyncValue.loading());

  final RewardsRepository _repository;

  Future<void> load() async {
    state = const AsyncValue.loading();
    state = await AsyncValue.guard(_fetch);
  }

  Future<void> refresh() async {
    state = await AsyncValue.guard(_fetch);
  }

  Future<CheckinData> _fetch() async {
    final statusResult = await _repository.fetchCheckinStatus();
    final status = switch (statusResult) {
      ApiSuccess(:final data) => data,
      ApiFailure(:final error) => throw error,
    };
    // Calendar is best-effort: the claim flow works without it.
    final calendar = (await _repository.fetchCheckinCalendar()).dataOrNull ?? CheckinCalendar.empty;
    return CheckinData(status: status, calendar: calendar);
  }

  /// Claim today's reward. Returns the [ClaimResult] on success (for the claim
  /// animation) or throws the [AppException] on failure.
  Future<ClaimResult> claim() async {
    final result = await _repository.claimCheckin();
    return switch (result) {
      ApiSuccess(:final data) => _onClaimed(data),
      ApiFailure(:final error) => throw error,
    };
  }

  ClaimResult _onClaimed(ClaimResult result) {
    final current = state.valueOrNull;
    if (current != null) {
      state = AsyncValue.data(
        current.copyWith(
          status: current.status.copyWith(
            canClaimToday: false,
            currentStreak: result.streakDay ?? current.status.currentStreak,
          ),
        ),
      );
    }
    return result;
  }
}

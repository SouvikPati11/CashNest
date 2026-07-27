import 'package:flutter/foundation.dart';

/// Daily check-in status (`GET /v1/checkin/status`).
@immutable
class CheckinStatus {
  const CheckinStatus({
    required this.canClaimToday,
    required this.currentStreak,
    required this.nextRewardCoins,
    this.lastCheckinDate,
  });

  final bool canClaimToday;
  final int currentStreak;
  final int nextRewardCoins;
  final String? lastCheckinDate;

  static int _int(dynamic v) => (v as num?)?.toInt() ?? 0;

  factory CheckinStatus.fromJson(Map<String, dynamic> json) {
    return CheckinStatus(
      canClaimToday: json['can_claim_today'] as bool? ?? false,
      currentStreak: _int(json['current_streak']),
      nextRewardCoins: _int(json['next_reward_coins']),
      lastCheckinDate: json['last_checkin_date'] as String?,
    );
  }

  CheckinStatus copyWith({
    bool? canClaimToday,
    int? currentStreak,
    int? nextRewardCoins,
    String? lastCheckinDate,
  }) {
    return CheckinStatus(
      canClaimToday: canClaimToday ?? this.canClaimToday,
      currentStreak: currentStreak ?? this.currentStreak,
      nextRewardCoins: nextRewardCoins ?? this.nextRewardCoins,
      lastCheckinDate: lastCheckinDate ?? this.lastCheckinDate,
    );
  }

  static const CheckinStatus empty = CheckinStatus(
    canClaimToday: false,
    currentStreak: 0,
    nextRewardCoins: 0,
  );
}

import 'package:flutter/foundation.dart';

/// Result of a reward-crediting mutation (check-in claim, scratch claim, spin,
/// task completion). Fields not returned by a given endpoint stay null.
@immutable
class ClaimResult {
  const ClaimResult({
    required this.coinsAwarded,
    this.newBalance,
    this.transactionUuid,
    this.streakDay,
  });

  final int coinsAwarded;
  final int? newBalance;
  final String? transactionUuid;
  final int? streakDay;

  static int _int(dynamic v) => (v as num?)?.toInt() ?? 0;

  factory ClaimResult.fromJson(Map<String, dynamic> json) {
    return ClaimResult(
      coinsAwarded: _int(json['coins_awarded']),
      newBalance: json['new_balance'] == null ? null : _int(json['new_balance']),
      transactionUuid: json['transaction_uuid'] as String?,
      streakDay: json['streak_day'] == null ? null : _int(json['streak_day']),
    );
  }
}

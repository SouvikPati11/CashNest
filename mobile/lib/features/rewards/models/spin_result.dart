import 'package:flutter/foundation.dart';

/// Outcome of a spin (`POST /v1/spin`). The client animation must land on
/// [segmentId] (the server is authoritative about the outcome).
@immutable
class SpinResult {
  const SpinResult({
    required this.segmentId,
    required this.rewardType,
    required this.rewardCoins,
    this.newBalance,
    this.spinsRemaining,
    this.transactionUuid,
  });

  final String segmentId;
  final String rewardType;
  final int rewardCoins;
  final int? newBalance;
  final int? spinsRemaining;
  final String? transactionUuid;

  static int _int(dynamic v) => (v as num?)?.toInt() ?? 0;

  factory SpinResult.fromJson(Map<String, dynamic> json) {
    return SpinResult(
      segmentId: json['segment_id'] as String? ?? '',
      rewardType: json['reward_type'] as String? ?? 'coins',
      rewardCoins: _int(json['reward_coins']),
      newBalance: json['new_balance'] == null ? null : _int(json['new_balance']),
      spinsRemaining: json['spins_remaining'] == null ? null : _int(json['spins_remaining']),
      transactionUuid: json['transaction_uuid'] as String?,
    );
  }
}

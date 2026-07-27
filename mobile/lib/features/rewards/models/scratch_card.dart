import 'package:flutter/foundation.dart';

/// A scratch card (`GET /v1/scratch/available`, `/history`, reveal result).
///
/// `status`: `issued` → `revealed` → `claimed` (or `expired`). [rewardCoins] is
/// populated once revealed.
@immutable
class ScratchCard {
  const ScratchCard({
    required this.uuid,
    required this.status,
    this.source,
    this.expiresAt,
    this.rewardCoins,
  });

  final String uuid;
  final String status;
  final String? source;
  final String? expiresAt;
  final int? rewardCoins;

  bool get isRevealed => status == 'revealed';
  bool get isClaimed => status == 'claimed';
  bool get isIssued => status == 'issued';

  factory ScratchCard.fromJson(Map<String, dynamic> json) {
    return ScratchCard(
      uuid: json['uuid'] as String? ?? '',
      status: json['status'] as String? ?? 'issued',
      source: json['source'] as String?,
      expiresAt: json['expires_at'] as String?,
      rewardCoins: json['reward_coins'] == null
          ? null
          : (json['reward_coins'] as num?)?.toInt(),
    );
  }

  ScratchCard copyWith({String? status, int? rewardCoins}) {
    return ScratchCard(
      uuid: uuid,
      status: status ?? this.status,
      source: source,
      expiresAt: expiresAt,
      rewardCoins: rewardCoins ?? this.rewardCoins,
    );
  }
}

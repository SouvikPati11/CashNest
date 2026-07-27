import 'package:flutter/foundation.dart';

/// The reward source a history entry came from.
enum RewardKind { checkin, scratch, spin, task }

/// A normalized reward-history item, merged from the scratch/spin/task history
/// endpoints into one sortable list for the Reward History screen.
@immutable
class RewardHistoryEntry {
  const RewardHistoryEntry({
    required this.kind,
    required this.id,
    required this.coins,
    this.status,
    this.createdAt,
  });

  final RewardKind kind;
  final String id;
  final int coins;
  final String? status;
  final DateTime? createdAt;

  static int _int(dynamic v) => (v as num?)?.toInt() ?? 0;

  static DateTime? _date(dynamic v) => v is String ? DateTime.tryParse(v) : null;

  /// Build from a scratch-history row.
  factory RewardHistoryEntry.scratch(Map<String, dynamic> json) {
    return RewardHistoryEntry(
      kind: RewardKind.scratch,
      id: json['uuid'] as String? ?? '',
      coins: _int(json['reward_coins']),
      status: json['status'] as String?,
      createdAt: _date(json['revealed_at'] ?? json['created_at'] ?? json['claimed_at']),
    );
  }

  /// Build from a spin-history row.
  factory RewardHistoryEntry.spin(Map<String, dynamic> json) {
    return RewardHistoryEntry(
      kind: RewardKind.spin,
      id: json['uuid'] as String? ?? json['id']?.toString() ?? '',
      coins: _int(json['reward_coins']),
      status: json['reward_type'] as String?,
      createdAt: _date(json['created_at'] ?? json['spun_at']),
    );
  }

  /// Build from a task-history (completion) row.
  factory RewardHistoryEntry.task(Map<String, dynamic> json) {
    return RewardHistoryEntry(
      kind: RewardKind.task,
      id: json['uuid'] as String? ?? json['completion_uuid'] as String? ?? '',
      coins: _int(json['coins_awarded'] ?? json['reward_coins']),
      status: json['status'] as String?,
      createdAt: _date(json['completed_at'] ?? json['created_at']),
    );
  }
}

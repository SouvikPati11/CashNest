import 'package:flutter/foundation.dart';

/// Result of starting or completing a task (`/tasks/{uuid}/start|complete`).
///
/// `status`: `started` | `credited` | `pending`. Reward fields are present only
/// when auto-verified (`credited`).
@immutable
class TaskActionResult {
  const TaskActionResult({
    required this.status,
    this.completionUuid,
    this.coinsAwarded,
    this.newBalance,
    this.transactionUuid,
  });

  final String status;
  final String? completionUuid;
  final int? coinsAwarded;
  final int? newBalance;
  final String? transactionUuid;

  bool get isCredited => status == 'credited';
  bool get isPending => status == 'pending';

  static int? _intOrNull(dynamic v) => v == null ? null : (v as num?)?.toInt();

  factory TaskActionResult.fromJson(Map<String, dynamic> json) {
    return TaskActionResult(
      status: json['status'] as String? ?? 'pending',
      completionUuid: json['completion_uuid'] as String?,
      coinsAwarded: _intOrNull(json['coins_awarded']),
      newBalance: _intOrNull(json['new_balance']),
      transactionUuid: json['transaction_uuid'] as String?,
    );
  }
}

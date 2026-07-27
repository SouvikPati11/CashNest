import 'package:flutter/foundation.dart';

/// Result of cancelling a pending withdrawal (`POST /v1/withdraw/{uuid}/cancel`).
@immutable
class CancelResult {
  const CancelResult({required this.status, this.refundedCoins, this.newBalance});

  final String status;
  final int? refundedCoins;
  final int? newBalance;

  static int? _intOrNull(dynamic v) => v == null ? null : (v as num?)?.toInt();

  factory CancelResult.fromJson(Map<String, dynamic> json) {
    return CancelResult(
      status: json['status'] as String? ?? 'cancelled',
      refundedCoins: _intOrNull(json['refunded_coins']),
      newBalance: _intOrNull(json['new_balance']),
    );
  }
}

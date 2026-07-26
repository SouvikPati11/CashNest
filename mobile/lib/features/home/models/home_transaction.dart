import 'package:flutter/foundation.dart';

/// A ledger entry for the recent-transactions preview
/// (`GET /v1/wallet/transactions`).
@immutable
class HomeTransaction {
  const HomeTransaction({
    required this.uuid,
    required this.direction,
    required this.amount,
    required this.type,
    this.description,
    this.createdAt,
  });

  final String uuid;
  final String direction;
  final int amount;
  final String type;
  final String? description;
  final String? createdAt;

  bool get isCredit => direction == 'credit';

  factory HomeTransaction.fromJson(Map<String, dynamic> json) {
    return HomeTransaction(
      uuid: json['uuid'] as String? ?? '',
      direction: json['direction'] as String? ?? 'credit',
      amount: (json['amount'] as num?)?.toInt() ?? 0,
      type: json['type'] as String? ?? '',
      description: json['description'] as String?,
      createdAt: json['created_at'] as String?,
    );
  }
}

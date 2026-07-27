import 'package:flutter/foundation.dart';

/// A wallet ledger entry (`GET /v1/wallet/transactions[/{uuid}]`).
///
/// The list endpoint returns the core fields; the detail endpoint additionally
/// populates [metadata] and [relatedTransaction].
@immutable
class WalletTransaction {
  const WalletTransaction({
    required this.uuid,
    required this.direction,
    required this.amount,
    this.balanceAfter,
    this.type = '',
    this.sourceModule,
    this.description,
    this.createdAt,
    this.metadata = const {},
    this.relatedTransaction,
  });

  final String uuid;

  /// `credit` or `debit`.
  final String direction;
  final int amount;
  final int? balanceAfter;
  final String type;
  final String? sourceModule;
  final String? description;
  final DateTime? createdAt;
  final Map<String, dynamic> metadata;
  final WalletTransaction? relatedTransaction;

  bool get isCredit => direction == 'credit';

  static int _int(dynamic v) => (v as num?)?.toInt() ?? 0;

  static DateTime? _date(dynamic v) => v is String ? DateTime.tryParse(v) : null;

  factory WalletTransaction.fromJson(Map<String, dynamic> json) {
    final related = json['related_transaction'];
    final metadata = json['metadata'];
    return WalletTransaction(
      uuid: json['uuid'] as String? ?? '',
      direction: json['direction'] as String? ?? 'credit',
      amount: _int(json['amount']),
      balanceAfter: json['balance_after'] == null ? null : _int(json['balance_after']),
      type: json['type'] as String? ?? '',
      sourceModule: json['source_module'] as String?,
      description: json['description'] as String?,
      createdAt: _date(json['created_at']),
      metadata: metadata is Map<String, dynamic> ? metadata : const {},
      relatedTransaction: related is Map<String, dynamic>
          ? WalletTransaction.fromJson(related)
          : null,
    );
  }
}

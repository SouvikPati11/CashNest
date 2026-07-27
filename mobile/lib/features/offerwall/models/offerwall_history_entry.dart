import 'package:flutter/foundation.dart';

/// A completed offerwall/CPA earning, read from the wallet ledger filtered to
/// offerwall/cpa credit rows (`GET /wallet/transactions?filter[type]=offerwall,cpa`).
@immutable
class OfferwallHistoryEntry {
  const OfferwallHistoryEntry({
    required this.uuid,
    required this.coins,
    required this.type,
    this.description,
    this.createdAt,
  });

  final String uuid;
  final int coins;
  final String type;
  final String? description;
  final DateTime? createdAt;

  static int _int(dynamic v) => (v as num?)?.toInt() ?? 0;

  static DateTime? _date(dynamic v) => v is String ? DateTime.tryParse(v) : null;

  factory OfferwallHistoryEntry.fromJson(Map<String, dynamic> json) {
    return OfferwallHistoryEntry(
      uuid: json['uuid'] as String? ?? '',
      coins: _int(json['amount']),
      type: json['type'] as String? ?? 'offerwall',
      description: json['description'] as String?,
      createdAt: _date(json['created_at']),
    );
  }
}

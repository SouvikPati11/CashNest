import 'package:flutter/foundation.dart';

/// A referral commission entry (`GET /v1/referral/earnings`).
@immutable
class ReferralEarning {
  const ReferralEarning({
    required this.commissionCoins,
    this.source,
    this.createdAt,
  });

  final int commissionCoins;
  final String? source;
  final DateTime? createdAt;

  static int _int(dynamic v) => (v as num?)?.toInt() ?? 0;

  static DateTime? _date(dynamic v) => v is String ? DateTime.tryParse(v) : null;

  factory ReferralEarning.fromJson(Map<String, dynamic> json) {
    return ReferralEarning(
      commissionCoins: _int(json['commission_coins']),
      source: json['source'] as String? ?? json['source_module'] as String?,
      createdAt: _date(json['created_at']),
    );
  }
}

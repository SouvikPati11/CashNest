import 'package:flutter/foundation.dart';

/// A payout method with limits + fee (`GET /v1/withdraw/methods`).
///
/// [detailSchema] maps required payment-detail field names to their type hint
/// (e.g. `{ "upi_id": "string" }`), driving the dynamic form fields.
@immutable
class WithdrawMethod {
  const WithdrawMethod({
    required this.code,
    required this.name,
    required this.minCoins,
    required this.maxCoins,
    this.feePercent = '0',
    this.detailSchema = const {},
  });

  final String code;
  final String name;
  final int minCoins;
  final int maxCoins;

  /// Fee as a fraction (e.g. `"0.0200"` = 2%).
  final String feePercent;
  final Map<String, dynamic> detailSchema;

  List<String> get detailFields => detailSchema.keys.toList(growable: false);

  double get feeFraction => double.tryParse(feePercent) ?? 0;

  static int _int(dynamic v) => (v as num?)?.toInt() ?? 0;

  factory WithdrawMethod.fromJson(Map<String, dynamic> json) {
    final schema = json['detail_schema'];
    return WithdrawMethod(
      code: json['code'] as String? ?? '',
      name: json['name'] as String? ?? '',
      minCoins: _int(json['min_coins']),
      maxCoins: _int(json['max_coins']),
      feePercent: json['fee_percent']?.toString() ?? '0',
      detailSchema: schema is Map<String, dynamic> ? schema : const {},
    );
  }
}

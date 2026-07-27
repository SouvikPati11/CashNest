import 'package:flutter/foundation.dart';

/// Coin→cash conversion rate + withdrawal thresholds
/// (`GET /v1/wallet/conversion`).
@immutable
class ConversionRate {
  const ConversionRate({
    required this.coinToCashRate,
    required this.currency,
    required this.minWithdrawCoins,
    required this.maxWithdrawCoins,
  });

  /// Cash per coin, kept as a string to preserve the backend's precision.
  final String coinToCashRate;
  final String currency;
  final int minWithdrawCoins;
  final int maxWithdrawCoins;

  static int _int(dynamic v) => (v as num?)?.toInt() ?? 0;

  /// Cash value of [coins] using this rate, or `null` if the rate is unusable.
  double? cashFor(int coins) {
    final rate = double.tryParse(coinToCashRate);
    if (rate == null) {
      return null;
    }
    return coins * rate;
  }

  factory ConversionRate.fromJson(Map<String, dynamic> json) {
    return ConversionRate(
      coinToCashRate: json['coin_to_cash_rate']?.toString() ?? '0',
      currency: json['currency'] as String? ?? 'INR',
      minWithdrawCoins: _int(json['min_withdraw_coins']),
      maxWithdrawCoins: _int(json['max_withdraw_coins']),
    );
  }
}

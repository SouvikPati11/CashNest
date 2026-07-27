import 'package:flutter/foundation.dart';

/// Coin→cash conversion rate + withdrawal thresholds (`GET /v1/wallet/conversion`).
///
/// Read by the withdraw feature to power the live conversion preview. This is a
/// plain read of the endpoint; the Wallet module is not imported or modified.
@immutable
class ConversionInfo {
  const ConversionInfo({
    required this.coinToCashRate,
    required this.currency,
    required this.minWithdrawCoins,
    required this.maxWithdrawCoins,
  });

  final String coinToCashRate;
  final String currency;
  final int minWithdrawCoins;
  final int maxWithdrawCoins;

  double get rate => double.tryParse(coinToCashRate) ?? 0;

  static int _int(dynamic v) => (v as num?)?.toInt() ?? 0;

  factory ConversionInfo.fromJson(Map<String, dynamic> json) {
    return ConversionInfo(
      coinToCashRate: json['coin_to_cash_rate']?.toString() ?? '0',
      currency: json['currency'] as String? ?? 'INR',
      minWithdrawCoins: _int(json['min_withdraw_coins']),
      maxWithdrawCoins: _int(json['max_withdraw_coins']),
    );
  }

  static const ConversionInfo empty = ConversionInfo(
    coinToCashRate: '0',
    currency: 'INR',
    minWithdrawCoins: 0,
    maxWithdrawCoins: 0,
  );
}

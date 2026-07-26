import 'package:flutter/foundation.dart';

/// Wallet balance summary for the home balance card (`GET /v1/wallet`).
///
/// Read-only home projection; the full Wallet feature is a separate module.
@immutable
class WalletBalance {
  const WalletBalance({
    required this.coinBalance,
    required this.coinReserved,
    required this.available,
    required this.cashBalance,
    required this.currency,
    required this.lifetimeEarned,
    required this.lifetimeSpent,
  });

  final int coinBalance;
  final int coinReserved;
  final int available;
  final String cashBalance;
  final String currency;
  final int lifetimeEarned;
  final int lifetimeSpent;

  static int _int(dynamic v) => (v as num?)?.toInt() ?? 0;

  factory WalletBalance.fromJson(Map<String, dynamic> json) {
    return WalletBalance(
      coinBalance: _int(json['coin_balance']),
      coinReserved: _int(json['coin_reserved']),
      available: _int(json['available']),
      cashBalance: json['cash_balance']?.toString() ?? '0.0000',
      currency: json['currency'] as String? ?? 'INR',
      lifetimeEarned: _int(json['lifetime_earned']),
      lifetimeSpent: _int(json['lifetime_spent']),
    );
  }

  static const WalletBalance empty = WalletBalance(
    coinBalance: 0,
    coinReserved: 0,
    available: 0,
    cashBalance: '0.0000',
    currency: 'INR',
    lifetimeEarned: 0,
    lifetimeSpent: 0,
  );
}

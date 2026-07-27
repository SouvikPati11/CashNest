import 'package:flutter/foundation.dart';

/// A client-side computed preview of a withdrawal: cash value, fee, and net
/// payout for a given coin amount. Purely for display — the server recomputes
/// authoritatively on request.
@immutable
class WithdrawQuote {
  const WithdrawQuote({
    required this.coins,
    required this.cash,
    required this.fee,
    required this.net,
    required this.currency,
  });

  final int coins;
  final double cash;
  final double fee;
  final double net;
  final String currency;

  /// Compute a quote from [coins] using the coin→cash [rate] and [feeFraction].
  factory WithdrawQuote.compute({
    required int coins,
    required double rate,
    required double feeFraction,
    required String currency,
  }) {
    final cash = coins * rate;
    final fee = cash * feeFraction;
    return WithdrawQuote(
      coins: coins,
      cash: cash,
      fee: fee,
      net: cash - fee,
      currency: currency,
    );
  }

  static const WithdrawQuote zero =
      WithdrawQuote(coins: 0, cash: 0, fee: 0, net: 0, currency: 'INR');
}

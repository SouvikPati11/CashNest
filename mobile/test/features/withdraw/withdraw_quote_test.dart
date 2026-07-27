import 'package:cashnest/features/withdraw/models/withdraw_quote.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('compute derives cash, fee, and net', () {
    final q = WithdrawQuote.compute(coins: 5000, rate: 0.001, feeFraction: 0.02, currency: 'INR');
    expect(q.coins, 5000);
    expect(q.cash, closeTo(5.0, 1e-9));
    expect(q.fee, closeTo(0.10, 1e-9));
    expect(q.net, closeTo(4.90, 1e-9));
    expect(q.currency, 'INR');
  });

  test('zero fee yields net == cash', () {
    final q = WithdrawQuote.compute(coins: 10000, rate: 0.001, feeFraction: 0, currency: 'INR');
    expect(q.cash, closeTo(10.0, 1e-9));
    expect(q.fee, 0);
    expect(q.net, closeTo(10.0, 1e-9));
  });
}

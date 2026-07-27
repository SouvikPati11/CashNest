import 'package:cashnest/features/withdraw/models/cancel_result.dart';
import 'package:cashnest/features/withdraw/models/conversion_info.dart';
import 'package:cashnest/features/withdraw/models/withdraw_detail.dart';
import 'package:cashnest/features/withdraw/models/withdraw_method.dart';
import 'package:cashnest/features/withdraw/models/withdraw_request.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('WithdrawMethod parses limits, fee, and schema fields', () {
    final m = WithdrawMethod.fromJson(const {
      'code': 'upi',
      'name': 'UPI',
      'min_coins': 5000,
      'max_coins': 100000,
      'fee_percent': '0.0200',
      'detail_schema': {'upi_id': 'string'},
    });
    expect(m.code, 'upi');
    expect(m.minCoins, 5000);
    expect(m.feeFraction, closeTo(0.02, 1e-9));
    expect(m.detailFields, ['upi_id']);
  });

  test('ConversionInfo exposes numeric rate', () {
    final c = ConversionInfo.fromJson(const {
      'coin_to_cash_rate': '0.00100000',
      'currency': 'INR',
      'min_withdraw_coins': 5000,
      'max_withdraw_coins': 100000,
    });
    expect(c.rate, closeTo(0.001, 1e-9));
    expect(c.currency, 'INR');
  });

  test('WithdrawRequest parses amounts + status', () {
    final r = WithdrawRequest.fromJson(const {
      'uuid': 'wr_1',
      'status': 'pending',
      'coins_amount': 5000,
      'cash_amount': '5.0000',
      'fee_amount': '0.1000',
      'net_amount': '4.9000',
      'currency': 'INR',
      'created_at': '2026-07-24T18:00:00Z',
    });
    expect(r.uuid, 'wr_1');
    expect(r.isPending, isTrue);
    expect(r.isCancellable, isTrue);
    expect(r.coinsAmount, 5000);
    expect(r.netAmount, '4.9000');
    expect(r.createdAt, isNotNull);
  });

  test('WithdrawDetail parses request + timeline', () {
    final d = WithdrawDetail.fromJson(const {
      'uuid': 'wr_1',
      'status': 'processing',
      'coins_amount': 5000,
      'cash_amount': '5',
      'net_amount': '4.9',
      'history': [
        {'to_status': 'pending', 'created_at': '2026-07-24T18:00:00Z'},
        {'from_status': 'pending', 'to_status': 'processing', 'note': 'In review'},
      ],
    });
    expect(d.request.status, 'processing');
    expect(d.history, hasLength(2));
    expect(d.history[1].fromStatus, 'pending');
    expect(d.history[1].note, 'In review');
  });

  test('CancelResult parses refund', () {
    final c = CancelResult.fromJson(const {
      'status': 'cancelled',
      'refunded_coins': 5000,
      'new_balance': 4200,
    });
    expect(c.status, 'cancelled');
    expect(c.refundedCoins, 5000);
    expect(c.newBalance, 4200);
  });
}

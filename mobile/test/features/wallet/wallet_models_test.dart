import 'package:cashnest/features/wallet/models/conversion_rate.dart';
import 'package:cashnest/features/wallet/models/transaction_filter.dart';
import 'package:cashnest/features/wallet/models/wallet_summary.dart';
import 'package:cashnest/features/wallet/models/wallet_transaction.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('WalletSummary.fromJson', () {
    test('parses backend shape', () {
      final s = WalletSummary.fromJson(const {
        'coin_balance': 4200,
        'coin_reserved': 0,
        'available': 4200,
        'cash_balance': '4.2000',
        'currency': 'INR',
        'lifetime_earned': 12000,
        'lifetime_spent': 7800,
      });
      expect(s.coinBalance, 4200);
      expect(s.available, 4200);
      expect(s.cashBalance, '4.2000');
      expect(s.lifetimeEarned, 12000);
      expect(s.lifetimeSpent, 7800);
    });

    test('defaults on empty json', () {
      final s = WalletSummary.fromJson(const {});
      expect(s.coinBalance, 0);
      expect(s.cashBalance, '0.0000');
      expect(s.currency, 'INR');
    });
  });

  group('ConversionRate', () {
    test('parses and computes cash value', () {
      final c = ConversionRate.fromJson(const {
        'coin_to_cash_rate': '0.00100000',
        'currency': 'INR',
        'min_withdraw_coins': 5000,
        'max_withdraw_coins': 100000,
      });
      expect(c.currency, 'INR');
      expect(c.minWithdrawCoins, 5000);
      expect(c.maxWithdrawCoins, 100000);
      expect(c.cashFor(5000), closeTo(5.0, 1e-9));
    });

    test('cashFor returns null for an unparseable rate', () {
      const c = ConversionRate(
        coinToCashRate: 'n/a',
        currency: 'INR',
        minWithdrawCoins: 0,
        maxWithdrawCoins: 0,
      );
      expect(c.cashFor(100), isNull);
    });
  });

  group('WalletTransaction.fromJson', () {
    test('parses list shape', () {
      final t = WalletTransaction.fromJson(const {
        'uuid': 'wt_a1',
        'direction': 'credit',
        'amount': 100,
        'balance_after': 4200,
        'type': 'offerwall',
        'source_module': 'offerwall',
        'description': 'AdGate offer',
        'created_at': '2026-07-24T18:00:00Z',
      });
      expect(t.uuid, 'wt_a1');
      expect(t.isCredit, isTrue);
      expect(t.balanceAfter, 4200);
      expect(t.sourceModule, 'offerwall');
      expect(t.createdAt, isNotNull);
      expect(t.createdAt!.toUtc().year, 2026);
    });

    test('parses detail shape with metadata and related transaction', () {
      final t = WalletTransaction.fromJson(const {
        'uuid': 'wt_h1',
        'direction': 'debit',
        'amount': 5000,
        'type': 'withdrawal_hold',
        'metadata': {'request_uuid': 'wr_1'},
        'related_transaction': {
          'uuid': 'wt_w1',
          'direction': 'debit',
          'amount': 5000,
          'type': 'withdraw',
        },
      });
      expect(t.isCredit, isFalse);
      expect(t.metadata['request_uuid'], 'wr_1');
      expect(t.relatedTransaction, isNotNull);
      expect(t.relatedTransaction!.type, 'withdraw');
    });

    test('tolerates missing optional fields', () {
      final t = WalletTransaction.fromJson(const {'uuid': 'x', 'direction': 'credit', 'amount': 1});
      expect(t.balanceAfter, isNull);
      expect(t.createdAt, isNull);
      expect(t.metadata, isEmpty);
      expect(t.relatedTransaction, isNull);
    });
  });

  group('TransactionFilter', () {
    test('toQuery maps only set fields with filter[...] keys', () {
      final filter = TransactionFilter(
        type: 'offerwall',
        direction: 'credit',
        dateFrom: DateTime.utc(2026, 7, 1),
        dateTo: DateTime.utc(2026, 7, 25),
      );
      final q = filter.toQuery();
      expect(q['filter[type]'], 'offerwall');
      expect(q['filter[direction]'], 'credit');
      expect(q['filter[date_from]'], '2026-07-01');
      expect(q['filter[date_to]'], '2026-07-25');
    });

    test('empty filter produces an empty query and is inactive', () {
      expect(TransactionFilter.none.toQuery(), isEmpty);
      expect(TransactionFilter.none.isActive, isFalse);
      expect(TransactionFilter.none.activeCount, 0);
    });

    test('activeCount counts a date range as one', () {
      final f = TransactionFilter(
        type: 'spin',
        dateFrom: DateTime.utc(2026, 1, 1),
        dateTo: DateTime.utc(2026, 2, 1),
      );
      expect(f.activeCount, 2);
      expect(f.isActive, isTrue);
    });

    test('copyWith reset clears a field', () {
      const f = TransactionFilter(type: 'spin', direction: 'credit');
      final cleared = f.copyWith(resetType: true);
      expect(cleared.type, isNull);
      expect(cleared.direction, 'credit');
    });

    test('equality is by value', () {
      expect(
        const TransactionFilter(type: 'spin'),
        equals(const TransactionFilter(type: 'spin')),
      );
    });
  });
}

import 'package:cashnest/features/wallet/models/conversion_rate.dart';
import 'package:cashnest/features/wallet/models/wallet_summary.dart';
import 'package:cashnest/features/wallet/models/wallet_transaction.dart';
import 'package:cashnest/features/wallet/presentation/widgets/balance_summary_card.dart';
import 'package:cashnest/features/wallet/presentation/widgets/conversion_card.dart';
import 'package:cashnest/features/wallet/presentation/widgets/transaction_tile.dart';
import 'package:cashnest/features/wallet/resources/wallet_formatters.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

Widget _host(Widget child) => MaterialApp(
      home: Scaffold(body: SingleChildScrollView(child: child)),
    );

void main() {
  group('WalletFormatters', () {
    test('coins groups thousands', () {
      expect(WalletFormatters.coins(4200, localeCode: 'en'), '4,200');
    });

    test('signedCoins prefixes credit/debit', () {
      expect(WalletFormatters.signedCoins(100, isCredit: true, localeCode: 'en'), '+100');
      expect(WalletFormatters.signedCoins(20, isCredit: false, localeCode: 'en'), '-20');
    });

    test('cash trims trailing zeros with currency', () {
      expect(WalletFormatters.cash('4.2000', 'INR'), 'INR 4.2');
    });

    test('rate preserves small values without rounding to zero', () {
      expect(WalletFormatters.rate('0.00100000', 'INR'), 'INR 0.001');
      expect(WalletFormatters.rate('0', 'INR'), 'INR 0');
    });
  });

  testWidgets('BalanceSummaryCard shows coin, cash, and metrics', (tester) async {
    await tester.pumpWidget(
      _host(
        const BalanceSummaryCard(
          summary: WalletSummary(
            coinBalance: 4200,
            coinReserved: 100,
            available: 4100,
            cashBalance: '4.2000',
            currency: 'INR',
            lifetimeEarned: 12000,
            lifetimeSpent: 7800,
          ),
        ),
      ),
    );

    expect(find.text('4,200'), findsOneWidget);
    expect(find.text('coins'), findsOneWidget);
    expect(find.text('Available'), findsOneWidget);
    expect(find.text('Reserved'), findsOneWidget);
    expect(find.textContaining('INR 4.2'), findsOneWidget);
  });

  testWidgets('ConversionCard shows rate and thresholds', (tester) async {
    await tester.pumpWidget(
      _host(
        const ConversionCard(
          conversion: ConversionRate(
            coinToCashRate: '0.001',
            currency: 'INR',
            minWithdrawCoins: 5000,
            maxWithdrawCoins: 100000,
          ),
        ),
      ),
    );

    expect(find.text('Conversion'), findsOneWidget);
    expect(find.textContaining('per coin'), findsOneWidget);
    expect(find.textContaining('5,000'), findsOneWidget);
    expect(find.textContaining('100,000'), findsOneWidget);
  });

  testWidgets('TransactionTile shows description, signed amount and fires onTap', (tester) async {
    var tapped = 0;
    await tester.pumpWidget(
      _host(
        TransactionTile(
          transaction: WalletTransaction(
            uuid: 'wt_a1',
            direction: 'credit',
            amount: 100,
            type: 'offerwall',
            description: 'AdGate offer',
            createdAt: DateTime.utc(2026, 7, 24, 18),
          ),
          onTap: () => tapped++,
        ),
      ),
    );

    expect(find.text('AdGate offer'), findsOneWidget);
    expect(find.text('+100'), findsOneWidget);

    await tester.tap(find.byType(TransactionTile));
    expect(tapped, 1);
  });

  testWidgets('TransactionTile falls back to the type label when no description', (tester) async {
    await tester.pumpWidget(
      _host(
        TransactionTile(
          transaction: WalletTransaction(
            uuid: 'wt_b1',
            direction: 'debit',
            amount: 20,
            type: 'withdraw',
            createdAt: DateTime.utc(2026, 7, 24, 18),
          ),
        ),
      ),
    );

    // 'withdraw' maps to the localized "Withdrawal" label (title); the subtitle
    // is the formatted date, so the label appears exactly once.
    expect(find.text('Withdrawal'), findsOneWidget);
    expect(find.text('-20'), findsOneWidget);
  });
}

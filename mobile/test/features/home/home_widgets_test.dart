import 'package:cashnest/features/home/models/home_transaction.dart';
import 'package:cashnest/features/home/models/wallet_balance.dart';
import 'package:cashnest/features/home/presentation/widgets/balance_card.dart';
import 'package:cashnest/features/home/presentation/widgets/preview_card.dart';
import 'package:cashnest/features/home/presentation/widgets/quick_actions.dart';
import 'package:cashnest/features/home/presentation/widgets/recent_transactions_preview.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

Widget _host(Widget child) => MaterialApp(
      home: Scaffold(body: SingleChildScrollView(child: child)),
    );

void main() {
  testWidgets('BalanceCard formats coins with thousands grouping', (tester) async {
    await tester.pumpWidget(
      _host(
        const BalanceCard(
          balance: WalletBalance(
            coinBalance: 4200,
            coinReserved: 100,
            available: 4100,
            cashBalance: '4.2000',
            currency: 'INR',
            lifetimeEarned: 0,
            lifetimeSpent: 0,
          ),
        ),
      ),
    );

    expect(find.text('4,200'), findsOneWidget);
    expect(find.text('coins'), findsOneWidget);
    expect(find.text('Available'), findsOneWidget);
    expect(find.text('Reserved'), findsOneWidget);
    expect(find.textContaining('INR 4.2000'), findsOneWidget);
  });

  testWidgets('PreviewCard renders title, subtitle and CTA and fires onTap', (tester) async {
    var tapped = 0;
    await tester.pumpWidget(
      _host(
        PreviewCard(
          icon: Icons.card_giftcard_outlined,
          title: 'Scratch card',
          subtitle: 'Scratch to reveal a reward.',
          actionLabel: 'Open',
          onTap: () => tapped++,
        ),
      ),
    );

    expect(find.text('Scratch card'), findsOneWidget);
    expect(find.text('Scratch to reveal a reward.'), findsOneWidget);

    await tester.tap(find.text('Open'));
    expect(tapped, 1);
  });

  testWidgets('QuickActions renders a localized label per key', (tester) async {
    final tapped = <String>[];
    await tester.pumpWidget(
      _host(
        QuickActions(
          actionKeys: const ['earn', 'spin', 'refer'],
          onAction: tapped.add,
        ),
      ),
    );

    expect(find.text('Earn'), findsOneWidget);
    expect(find.text('Spin'), findsOneWidget);
    expect(find.text('Refer'), findsOneWidget);

    await tester.tap(find.text('Earn'));
    expect(tapped, ['earn']);
  });

  testWidgets('RecentTransactionsPreview shows empty state when no activity', (tester) async {
    await tester.pumpWidget(
      _host(const RecentTransactionsPreview(transactions: [])),
    );

    expect(find.text('No transactions yet.'), findsOneWidget);
  });

  testWidgets('RecentTransactionsPreview lists credits and debits with signs', (tester) async {
    await tester.pumpWidget(
      _host(
        const RecentTransactionsPreview(
          transactions: [
            HomeTransaction(uuid: 't1', direction: 'credit', amount: 50, type: 'bonus', description: 'Daily bonus'),
            HomeTransaction(uuid: 't2', direction: 'debit', amount: 20, type: 'withdraw'),
          ],
        ),
      ),
    );

    expect(find.text('Daily bonus'), findsOneWidget);
    expect(find.text('+50'), findsOneWidget);
    expect(find.text('-20'), findsOneWidget);
    // Falls back to a humanized type when there is no description.
    expect(find.text('Withdraw'), findsOneWidget);
  });
}

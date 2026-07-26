import 'package:cashnest/shared/widgets/app_button.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  Widget wrap(Widget child) => MaterialApp(home: Scaffold(body: child));

  group('AppButton', () {
    testWidgets('shows a spinner and hides the label while loading', (tester) async {
      await tester.pumpWidget(
        wrap(AppButton(label: 'Continue', onPressed: () {}, isLoading: true)),
      );

      expect(find.byType(CircularProgressIndicator), findsOneWidget);
      expect(find.text('Continue'), findsNothing);
    });

    testWidgets('invokes onPressed when tapped', (tester) async {
      var tapped = false;
      await tester.pumpWidget(
        wrap(AppButton(label: 'Continue', onPressed: () => tapped = true)),
      );

      await tester.tap(find.text('Continue'));
      expect(tapped, isTrue);
    });

    testWidgets('is disabled while loading', (tester) async {
      var tapped = false;
      await tester.pumpWidget(
        wrap(AppButton(label: 'Continue', onPressed: () => tapped = true, isLoading: true)),
      );

      await tester.tap(find.byType(FilledButton));
      expect(tapped, isFalse);
    });
  });
}

import 'package:cashnest/features/rewards/resources/reward_visuals.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('RewardVisuals.hexToColor', () {
    test('parses 6-digit hex with #', () {
      expect(RewardVisuals.hexToColor('#FFCC00'), const Color(0xFFFFCC00));
    });

    test('parses without # and 8-digit hex', () {
      expect(RewardVisuals.hexToColor('00FF00'), const Color(0xFF00FF00));
      expect(RewardVisuals.hexToColor('#8000FF00'), const Color(0x8000FF00));
    });

    test('falls back on malformed input', () {
      expect(RewardVisuals.hexToColor('nope'), const Color(0xFF9E9E9E));
      expect(RewardVisuals.hexToColor('#12', fallback: const Color(0xFF000000)),
          const Color(0xFF000000));
    });
  });

  test('coins groups thousands', () {
    expect(RewardVisuals.coins(4200, localeCode: 'en'), '4,200');
  });

  test('dateTime formats without locale data dependency', () {
    // Uses local time; assert the date part is stable regardless of timezone
    // by checking the month/year tokens are present.
    final formatted = RewardVisuals.dateTime(DateTime(2026, 7, 27, 18, 5));
    expect(formatted, '27 Jul 2026, 18:05');
  });

  test('onColor picks readable foreground', () {
    expect(RewardVisuals.onColor(const Color(0xFFFFFFFF)), Colors.black);
    expect(RewardVisuals.onColor(const Color(0xFF000000)), Colors.white);
  });
}

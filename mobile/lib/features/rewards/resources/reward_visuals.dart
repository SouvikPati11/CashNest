import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

/// Visual helpers for the rewards module (segment colors, coin formatting).
abstract final class RewardVisuals {
  const RewardVisuals._();

  /// Parse a `#RRGGBB` / `#AARRGGBB` (or without `#`) hex string into a [Color],
  /// falling back to [fallback] when the string is malformed.
  static Color hexToColor(String hex, {Color fallback = const Color(0xFF9E9E9E)}) {
    var value = hex.trim().replaceFirst('#', '');
    if (value.length == 6) {
      value = 'FF$value';
    }
    if (value.length != 8) {
      return fallback;
    }
    final parsed = int.tryParse(value, radix: 16);
    return parsed == null ? fallback : Color(parsed);
  }

  /// A readable foreground color (black/white) for text on [background].
  static Color onColor(Color background) {
    return background.computeLuminance() > 0.5 ? Colors.black : Colors.white;
  }

  /// Group a coin count with locale-aware thousands separators, e.g. `4,200`.
  static String coins(int value, {String? localeCode}) {
    return NumberFormat.decimalPattern(localeCode).format(value);
  }

  /// Short date + time, e.g. `27 Jul 2026, 18:00`.
  ///
  /// Formatted manually so it never depends on `intl`'s locale date-symbol data
  /// (which the app does not initialize).
  static String dateTime(DateTime value) {
    final d = value.toLocal();
    final hh = d.hour.toString().padLeft(2, '0');
    final mm = d.minute.toString().padLeft(2, '0');
    return '${d.day} ${_monthAbbr[d.month - 1]} ${d.year}, $hh:$mm';
  }

  static const List<String> _monthAbbr = [
    'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
  ];
}

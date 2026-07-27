import 'package:intl/intl.dart';

/// Formatting helpers for wallet values, locale-aware via [intl].
abstract final class WalletFormatters {
  const WalletFormatters._();

  /// Group a coin count with locale-aware thousands separators, e.g. `4,200`.
  static String coins(int value, {String? localeCode}) {
    return NumberFormat.decimalPattern(localeCode).format(value);
  }

  /// A signed coin amount for a transaction row, e.g. `+100` / `-20`.
  static String signedCoins(int value, {required bool isCredit, String? localeCode}) {
    final sign = isCredit ? '+' : '-';
    return '$sign${coins(value, localeCode: localeCode)}';
  }

  /// Format the cash string (already fixed-precision from the backend) with its
  /// currency code, trimming trailing zeros for readability, e.g. `INR 4.20`.
  static String cash(String cashBalance, String currency) {
    final parsed = double.tryParse(cashBalance);
    if (parsed == null) {
      return '$currency $cashBalance';
    }
    final formatted = NumberFormat('#,##0.##').format(parsed);
    return '$currency $formatted';
  }

  /// Format a conversion rate with its currency, preserving small values by
  /// trimming trailing zeros instead of rounding, e.g. `0.00100000` → `INR 0.001`.
  static String rate(String raw, String currency) {
    var value = raw;
    if (value.contains('.')) {
      value = value.replaceFirst(RegExp(r'0+$'), '').replaceFirst(RegExp(r'\.$'), '');
    }
    if (value.isEmpty) {
      value = '0';
    }
    return '$currency $value';
  }

  /// Short date, e.g. `27 Jul 2026`.
  ///
  /// Formatted manually rather than via [DateFormat] so it never depends on
  /// `intl`'s locale date-symbol data (which the app does not initialize, and
  /// which would otherwise throw for non-`en_US` locales).
  static String date(DateTime value) {
    final d = value.toLocal();
    return '${d.day} ${_monthAbbr[d.month - 1]} ${d.year}';
  }

  /// Absolute date + time, e.g. `27 Jul 2026, 18:00`.
  static String dateTime(DateTime value) {
    final d = value.toLocal();
    return '${date(value)}, ${_two(d.hour)}:${_two(d.minute)}';
  }

  static String _two(int n) => n.toString().padLeft(2, '0');

  static const List<String> _monthAbbr = [
    'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
  ];
}

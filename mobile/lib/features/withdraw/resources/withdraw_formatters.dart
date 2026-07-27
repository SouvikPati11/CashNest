import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

/// Formatting + visual helpers for the withdraw feature.
abstract final class WithdrawFormatters {
  const WithdrawFormatters._();

  static String coins(int value, {String? localeCode}) {
    return NumberFormat.decimalPattern(localeCode).format(value);
  }

  /// Format a fixed-precision cash string with its currency, trimming trailing
  /// zeros, e.g. `("5.0000", "INR")` → `INR 5`.
  static String cash(String amount, String currency) {
    final parsed = double.tryParse(amount);
    if (parsed == null) {
      return '$currency $amount';
    }
    return '$currency ${NumberFormat('#,##0.##').format(parsed)}';
  }

  /// Format a computed double cash value with its currency.
  static String cashValue(double amount, String currency) {
    return '$currency ${NumberFormat('#,##0.##').format(amount)}';
  }

  /// Short date + time, formatted manually so it never depends on `intl` locale
  /// date-symbol data (which the app does not initialize).
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

  /// A semantic color for a withdrawal status.
  static Color statusColor(String status, ColorScheme scheme) {
    switch (status) {
      case 'paid':
      case 'completed':
        return Colors.green.shade600;
      case 'rejected':
      case 'failed':
        return scheme.error;
      case 'cancelled':
        return scheme.onSurfaceVariant;
      case 'processing':
        return Colors.orange.shade700;
      case 'pending':
      default:
        return scheme.primary;
    }
  }
}

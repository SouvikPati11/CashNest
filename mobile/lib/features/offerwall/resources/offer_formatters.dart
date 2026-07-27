import 'package:intl/intl.dart';

/// Formatting helpers for the offerwall feature.
abstract final class OfferFormatters {
  const OfferFormatters._();

  /// Group a coin count with locale-aware thousands separators, e.g. `4,200`.
  static String coins(int value, {String? localeCode}) {
    return NumberFormat.decimalPattern(localeCode).format(value);
  }

  /// Short date + time formatted manually (no dependency on `intl` locale
  /// date-symbol data, which the app does not initialize).
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

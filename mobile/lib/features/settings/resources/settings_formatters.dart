/// Formatting helpers for the settings feature.
abstract final class SettingsFormatters {
  const SettingsFormatters._();

  /// Short date, formatted manually so it never depends on `intl` locale
  /// date-symbol data (which the app does not initialize), e.g. `27 Jul 2026`.
  static String date(DateTime value) {
    final d = value.toLocal();
    return '${d.day} ${_monthAbbr[d.month - 1]} ${d.year}';
  }

  /// Short date + time, e.g. `27 Jul 2026, 18:00`.
  static String dateTime(DateTime value) {
    final d = value.toLocal();
    final hh = d.hour.toString().padLeft(2, '0');
    final mm = d.minute.toString().padLeft(2, '0');
    return '${date(value)}, $hh:$mm';
  }

  static const List<String> _monthAbbr = [
    'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
  ];
}

import 'package:flutter/material.dart';

/// Visual + formatting helpers for notifications.
abstract final class NotificationVisuals {
  const NotificationVisuals._();

  /// An icon for a notification type.
  static IconData iconFor(String type) {
    switch (type) {
      case 'transactional':
      case 'withdraw':
      case 'wallet':
        return Icons.account_balance_wallet_outlined;
      case 'reward':
      case 'promotional':
        return Icons.card_giftcard_outlined;
      case 'referral':
        return Icons.group_outlined;
      case 'system':
      default:
        return Icons.notifications_outlined;
    }
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
}

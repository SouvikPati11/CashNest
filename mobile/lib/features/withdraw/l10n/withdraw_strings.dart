import 'package:flutter/widgets.dart';

/// Feature-scoped localization for the withdraw feature (English + Bangla).
class WithdrawStrings {
  const WithdrawStrings(this._values);

  final Map<String, String> _values;

  static WithdrawStrings of(BuildContext context) {
    final code = Localizations.localeOf(context).languageCode;
    return WithdrawStrings(code == 'bn' ? _bn : _en);
  }

  String _t(String key) => _values[key] ?? key;

  String get title => _t('title');
  String get method => _t('method');
  String get amount => _t('amount');
  String get amountHint => _t('amountHint');
  String get coins => _t('coins');
  String get paymentDetails => _t('paymentDetails');
  String get cashValue => _t('cashValue');
  String get fee => _t('fee');
  String get youReceive => _t('youReceive');
  String get conversionRate => _t('conversionRate');
  String get review => _t('review');
  String get summary => _t('summary');
  String get confirm => _t('confirm');
  String get submitting => _t('submitting');
  String get requestSubmitted => _t('requestSubmitted');
  String get belowMin => _t('belowMin');
  String get aboveMax => _t('aboveMax');
  String get fillDetails => _t('fillDetails');

  String get historyTitle => _t('historyTitle');
  String get noHistory => _t('noHistory');
  String get noHistoryBody => _t('noHistoryBody');
  String get filterStatus => _t('filterStatus');
  String get all => _t('all');

  String get detailTitle => _t('detailTitle');
  String get statusTimeline => _t('statusTimeline');
  String get cancelRequest => _t('cancelRequest');
  String get cancelConfirmTitle => _t('cancelConfirmTitle');
  String get cancelConfirmBody => _t('cancelConfirmBody');
  String get cancelled => _t('cancelled');
  String get keep => _t('keep');

  String get errorTitle => _t('errorTitle');
  String get loadMoreError => _t('loadMoreError');
  String get noResults => _t('noResults');
  String get noResultsBody => _t('noResultsBody');

  String statusLabel(String status) => _t('status_$status');

  /// "Min {min} · Max {max}" for coins.
  String minMax(String min, String max) => '${_t('min')} $min · ${_t('max')} $max';

  /// Humanize a payment-detail field key (e.g. `upi_id` → `Upi Id`).
  String fieldLabel(String key) {
    final mapped = _values['field_$key'];
    if (mapped != null) {
      return mapped;
    }
    return key
        .split(RegExp(r'[_\s]+'))
        .where((w) => w.isNotEmpty)
        .map((w) => w[0].toUpperCase() + w.substring(1))
        .join(' ');
  }

  static const Map<String, String> _en = {
    'title': 'Withdraw',
    'method': 'Method',
    'amount': 'Amount',
    'amountHint': 'Coins to withdraw',
    'coins': 'coins',
    'paymentDetails': 'Payment details',
    'cashValue': 'Cash value',
    'fee': 'Fee',
    'youReceive': 'You receive',
    'conversionRate': 'Rate',
    'review': 'Review withdrawal',
    'summary': 'Withdrawal summary',
    'confirm': 'Confirm withdrawal',
    'submitting': 'Submitting…',
    'requestSubmitted': 'Withdrawal requested.',
    'belowMin': 'Amount is below the minimum.',
    'aboveMax': 'Amount is above the maximum.',
    'fillDetails': 'Fill in all payment details.',
    'historyTitle': 'Withdrawal history',
    'noHistory': 'No withdrawals yet',
    'noHistoryBody': 'Your withdrawal requests will appear here.',
    'filterStatus': 'Status',
    'all': 'All',
    'detailTitle': 'Withdrawal detail',
    'statusTimeline': 'Status timeline',
    'cancelRequest': 'Cancel request',
    'cancelConfirmTitle': 'Cancel withdrawal?',
    'cancelConfirmBody': 'The reserved coins will be returned to your balance.',
    'cancelled': 'Withdrawal cancelled.',
    'keep': 'Keep',
    'errorTitle': 'Something went wrong.',
    'loadMoreError': 'Could not load more. Tap to retry.',
    'noResults': 'No matches',
    'noResultsBody': 'No withdrawals match this filter.',
    'min': 'Min',
    'max': 'Max',
    'status_pending': 'Pending',
    'status_processing': 'Processing',
    'status_paid': 'Paid',
    'status_completed': 'Completed',
    'status_rejected': 'Rejected',
    'status_cancelled': 'Cancelled',
    'status_failed': 'Failed',
    'field_upi_id': 'UPI ID',
    'field_account_number': 'Account number',
    'field_ifsc': 'IFSC code',
    'field_wallet_address': 'Wallet address',
    'field_email': 'Email',
    'field_phone': 'Phone',
  };

  static const Map<String, String> _bn = {
    'title': 'উত্তোলন',
    'method': 'পদ্ধতি',
    'amount': 'পরিমাণ',
    'amountHint': 'উত্তোলনের কয়েন',
    'coins': 'কয়েন',
    'paymentDetails': 'পেমেন্ট বিবরণ',
    'cashValue': 'নগদ মূল্য',
    'fee': 'ফি',
    'youReceive': 'আপনি পাবেন',
    'conversionRate': 'হার',
    'review': 'উত্তোলন পর্যালোচনা',
    'summary': 'উত্তোলনের সারসংক্ষেপ',
    'confirm': 'উত্তোলন নিশ্চিত করুন',
    'submitting': 'জমা হচ্ছে…',
    'requestSubmitted': 'উত্তোলনের অনুরোধ জমা হয়েছে।',
    'belowMin': 'পরিমাণ সর্বনিম্নের চেয়ে কম।',
    'aboveMax': 'পরিমাণ সর্বোচ্চের চেয়ে বেশি।',
    'fillDetails': 'সমস্ত পেমেন্ট বিবরণ পূরণ করুন।',
    'historyTitle': 'উত্তোলনের ইতিহাস',
    'noHistory': 'এখনও কোনো উত্তোলন নেই',
    'noHistoryBody': 'আপনার উত্তোলনের অনুরোধ এখানে দেখা যাবে।',
    'filterStatus': 'অবস্থা',
    'all': 'সব',
    'detailTitle': 'উত্তোলনের বিবরণ',
    'statusTimeline': 'অবস্থার সময়রেখা',
    'cancelRequest': 'অনুরোধ বাতিল করুন',
    'cancelConfirmTitle': 'উত্তোলন বাতিল?',
    'cancelConfirmBody': 'সংরক্ষিত কয়েন আপনার ব্যালেন্সে ফেরত দেওয়া হবে।',
    'cancelled': 'উত্তোলন বাতিল হয়েছে।',
    'keep': 'রাখুন',
    'errorTitle': 'কিছু ভুল হয়েছে।',
    'loadMoreError': 'আরও লোড করা যায়নি। আবার চেষ্টা করতে ট্যাপ করুন।',
    'noResults': 'কোনো মিল নেই',
    'noResultsBody': 'এই ফিল্টারের সাথে কোনো উত্তোলন মেলেনি।',
    'min': 'সর্বনিম্ন',
    'max': 'সর্বোচ্চ',
    'status_pending': 'বিচারাধীন',
    'status_processing': 'প্রক্রিয়াধীন',
    'status_paid': 'পরিশোধিত',
    'status_completed': 'সম্পন্ন',
    'status_rejected': 'প্রত্যাখ্যাত',
    'status_cancelled': 'বাতিল',
    'status_failed': 'ব্যর্থ',
    'field_upi_id': 'ইউপিআই আইডি',
    'field_account_number': 'অ্যাকাউন্ট নম্বর',
    'field_ifsc': 'আইএফএসসি কোড',
    'field_wallet_address': 'ওয়ালেট ঠিকানা',
    'field_email': 'ইমেইল',
    'field_phone': 'ফোন',
  };
}

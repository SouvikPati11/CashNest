import 'package:flutter/widgets.dart';

/// Feature-scoped localization for the wallet module (English + Bangla).
class WalletStrings {
  const WalletStrings(this._values);

  final Map<String, String> _values;

  static WalletStrings of(BuildContext context) {
    final code = Localizations.localeOf(context).languageCode;
    return WalletStrings(code == 'bn' ? _bn : _en);
  }

  String _t(String key) => _values[key] ?? key;

  String get title => _t('title');
  String get balanceTitle => _t('balanceTitle');
  String get coinBalance => _t('coinBalance');
  String get cashBalance => _t('cashBalance');
  String get coins => _t('coins');
  String get available => _t('available');
  String get reserved => _t('reserved');
  String get lifetimeEarned => _t('lifetimeEarned');
  String get lifetimeSpent => _t('lifetimeSpent');
  String get conversionTitle => _t('conversionTitle');
  String get conversionRate => _t('conversionRate');
  String get perCoin => _t('perCoin');
  String get minWithdraw => _t('minWithdraw');
  String get maxWithdraw => _t('maxWithdraw');
  String get transactionHistory => _t('transactionHistory');
  String get searchHint => _t('searchHint');
  String get filters => _t('filters');
  String get filterType => _t('filterType');
  String get filterDirection => _t('filterDirection');
  String get all => _t('all');
  String get credit => _t('credit');
  String get debit => _t('debit');
  String get dateRange => _t('dateRange');
  String get from => _t('from');
  String get to => _t('to');
  String get apply => _t('apply');
  String get clear => _t('clear');
  String get clearFilters => _t('clearFilters');
  String get noTransactions => _t('noTransactions');
  String get noTransactionsBody => _t('noTransactionsBody');
  String get noResults => _t('noResults');
  String get noResultsBody => _t('noResultsBody');
  String get errorTitle => _t('errorTitle');
  String get loadMoreError => _t('loadMoreError');
  String get transactionDetails => _t('transactionDetails');
  String get amount => _t('amount');
  String get type => _t('type');
  String get direction => _t('direction');
  String get date => _t('date');
  String get balanceAfter => _t('balanceAfter');
  String get reference => _t('reference');
  String get sourceModule => _t('sourceModule');
  String get relatedTransaction => _t('relatedTransaction');
  String get details => _t('details');
  String get offlineHint => _t('offlineHint');

  /// Human label for a transaction/ledger `type`, falling back to a humanized
  /// form of the raw key so unknown/newer types still read cleanly.
  String typeLabel(String key) {
    final mapped = _values['type_$key'];
    if (mapped != null) {
      return mapped;
    }
    return key
        .split(RegExp(r'[_\s]+'))
        .where((w) => w.isNotEmpty)
        .map((w) => w[0].toUpperCase() + w.substring(1))
        .join(' ');
  }

  /// The canonical set of ledger types offered in the filter UI.
  static const List<String> filterableTypes = [
    'checkin',
    'scratch',
    'spin',
    'tasks',
    'offerwall',
    'cpa',
    'referral',
    'withdraw',
    'reversal',
    'adjustment',
  ];

  static const Map<String, String> _en = {
    'title': 'Wallet',
    'balanceTitle': 'Total balance',
    'coinBalance': 'Coin balance',
    'cashBalance': 'Cash value',
    'coins': 'coins',
    'available': 'Available',
    'reserved': 'Reserved',
    'lifetimeEarned': 'Lifetime earned',
    'lifetimeSpent': 'Lifetime spent',
    'conversionTitle': 'Conversion',
    'conversionRate': 'Conversion rate',
    'perCoin': 'per coin',
    'minWithdraw': 'Min withdraw',
    'maxWithdraw': 'Max withdraw',
    'transactionHistory': 'Transaction history',
    'searchHint': 'Search transactions',
    'filters': 'Filters',
    'filterType': 'Type',
    'filterDirection': 'Direction',
    'all': 'All',
    'credit': 'Credit',
    'debit': 'Debit',
    'dateRange': 'Date range',
    'from': 'From',
    'to': 'To',
    'apply': 'Apply',
    'clear': 'Clear',
    'clearFilters': 'Clear filters',
    'noTransactions': 'No transactions yet',
    'noTransactionsBody': 'Your wallet activity will show up here.',
    'noResults': 'No matches',
    'noResultsBody': 'No transactions match your search or filters.',
    'errorTitle': 'Could not load your wallet.',
    'loadMoreError': 'Could not load more. Tap to retry.',
    'transactionDetails': 'Transaction details',
    'amount': 'Amount',
    'type': 'Type',
    'direction': 'Direction',
    'date': 'Date',
    'balanceAfter': 'Balance after',
    'reference': 'Reference',
    'sourceModule': 'Source',
    'relatedTransaction': 'Related transaction',
    'details': 'Details',
    'offlineHint': 'You are offline. Showing cached data.',
    'type_checkin': 'Daily check-in',
    'type_scratch': 'Scratch card',
    'type_spin': 'Spin wheel',
    'type_tasks': 'Task reward',
    'type_offerwall': 'Offerwall',
    'type_cpa': 'CPA offer',
    'type_referral': 'Referral',
    'type_withdraw': 'Withdrawal',
    'type_withdrawal_hold': 'Withdrawal hold',
    'type_reversal': 'Reversal',
    'type_adjustment': 'Adjustment',
  };

  static const Map<String, String> _bn = {
    'title': 'ওয়ালেট',
    'balanceTitle': 'মোট ব্যালেন্স',
    'coinBalance': 'কয়েন ব্যালেন্স',
    'cashBalance': 'নগদ মূল্য',
    'coins': 'কয়েন',
    'available': 'উপলব্ধ',
    'reserved': 'সংরক্ষিত',
    'lifetimeEarned': 'মোট আয়',
    'lifetimeSpent': 'মোট ব্যয়',
    'conversionTitle': 'রূপান্তর',
    'conversionRate': 'রূপান্তর হার',
    'perCoin': 'প্রতি কয়েন',
    'minWithdraw': 'সর্বনিম্ন উত্তোলন',
    'maxWithdraw': 'সর্বোচ্চ উত্তোলন',
    'transactionHistory': 'লেনদেনের ইতিহাস',
    'searchHint': 'লেনদেন খুঁজুন',
    'filters': 'ফিল্টার',
    'filterType': 'ধরন',
    'filterDirection': 'দিক',
    'all': 'সব',
    'credit': 'জমা',
    'debit': 'খরচ',
    'dateRange': 'তারিখের পরিসর',
    'from': 'থেকে',
    'to': 'পর্যন্ত',
    'apply': 'প্রয়োগ করুন',
    'clear': 'মুছুন',
    'clearFilters': 'ফিল্টার মুছুন',
    'noTransactions': 'এখনও কোনো লেনদেন নেই',
    'noTransactionsBody': 'আপনার ওয়ালেট কার্যকলাপ এখানে দেখা যাবে।',
    'noResults': 'কোনো মিল নেই',
    'noResultsBody': 'আপনার অনুসন্ধান বা ফিল্টারের সাথে কোনো লেনদেন মেলেনি।',
    'errorTitle': 'আপনার ওয়ালেট লোড করা যায়নি।',
    'loadMoreError': 'আরও লোড করা যায়নি। আবার চেষ্টা করতে ট্যাপ করুন।',
    'transactionDetails': 'লেনদেনের বিবরণ',
    'amount': 'পরিমাণ',
    'type': 'ধরন',
    'direction': 'দিক',
    'date': 'তারিখ',
    'balanceAfter': 'পরবর্তী ব্যালেন্স',
    'reference': 'রেফারেন্স',
    'sourceModule': 'উৎস',
    'relatedTransaction': 'সম্পর্কিত লেনদেন',
    'details': 'বিবরণ',
    'offlineHint': 'আপনি অফলাইনে আছেন। ক্যাশ করা তথ্য দেখানো হচ্ছে।',
    'type_checkin': 'দৈনিক চেক-ইন',
    'type_scratch': 'স্ক্র্যাচ কার্ড',
    'type_spin': 'স্পিন হুইল',
    'type_tasks': 'টাস্ক পুরস্কার',
    'type_offerwall': 'অফারওয়াল',
    'type_cpa': 'সিপিএ অফার',
    'type_referral': 'রেফারেল',
    'type_withdraw': 'উত্তোলন',
    'type_withdrawal_hold': 'উত্তোলন হোল্ড',
    'type_reversal': 'রিভার্সাল',
    'type_adjustment': 'সমন্বয়',
  };
}

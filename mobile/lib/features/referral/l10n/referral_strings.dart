import 'package:flutter/widgets.dart';

/// Feature-scoped localization for the referral + leaderboard feature (EN + BN).
class ReferralStrings {
  const ReferralStrings(this._values);

  final Map<String, String> _values;

  static ReferralStrings of(BuildContext context) {
    final code = Localizations.localeOf(context).languageCode;
    return ReferralStrings(code == 'bn' ? _bn : _en);
  }

  String _t(String key) => _values[key] ?? key;

  String get title => _t('title');
  String get subtitle => _t('subtitle');
  String get yourCode => _t('yourCode');
  String get copyCode => _t('copyCode');
  String get copyLink => _t('copyLink');
  String get copied => _t('copied');
  String get totalReferrals => _t('totalReferrals');
  String get qualified => _t('qualified');
  String get totalEarned => _t('totalEarned');
  String get commission => _t('commission');
  String get haveACode => _t('haveACode');
  String get enterCode => _t('enterCode');
  String get apply => _t('apply');
  String get applying => _t('applying');
  String get applied => _t('applied');

  String get tabReferrals => _t('tabReferrals');
  String get tabEarnings => _t('tabEarnings');
  String get tabLeaderboard => _t('tabLeaderboard');

  String get searchHint => _t('searchHint');
  String get filterStatus => _t('filterStatus');
  String get all => _t('all');
  String get statusPending => _t('statusPending');
  String get statusQualified => _t('statusQualified');
  String get joined => _t('joined');
  String get noReferrals => _t('noReferrals');
  String get noReferralsBody => _t('noReferralsBody');

  String get noEarnings => _t('noEarnings');
  String get noEarningsBody => _t('noEarningsBody');

  String get periodDaily => _t('periodDaily');
  String get periodWeekly => _t('periodWeekly');
  String get periodMonthly => _t('periodMonthly');
  String get periodAllTime => _t('periodAllTime');
  String get yourRank => _t('yourRank');
  String get notRanked => _t('notRanked');
  String get points => _t('points');
  String get noLeaderboard => _t('noLeaderboard');
  String get noLeaderboardBody => _t('noLeaderboardBody');

  String get coins => _t('coins');
  String get errorTitle => _t('errorTitle');
  String get loadMoreError => _t('loadMoreError');
  String get noResults => _t('noResults');
  String get noResultsBody => _t('noResultsBody');

  String statusLabel(String status) => _t('status_$status');

  static const Map<String, String> _en = {
    'title': 'Refer & earn',
    'subtitle': 'Invite friends and earn coins when they join.',
    'yourCode': 'Your referral code',
    'copyCode': 'Copy code',
    'copyLink': 'Copy link',
    'copied': 'Copied to clipboard.',
    'totalReferrals': 'Referrals',
    'qualified': 'Qualified',
    'totalEarned': 'Earned',
    'commission': 'Commission',
    'haveACode': 'Have a referral code?',
    'enterCode': 'Enter referral code',
    'apply': 'Apply',
    'applying': 'Applying…',
    'applied': 'Referral code applied!',
    'tabReferrals': 'Referrals',
    'tabEarnings': 'Earnings',
    'tabLeaderboard': 'Leaderboard',
    'searchHint': 'Search',
    'filterStatus': 'Status',
    'all': 'All',
    'statusPending': 'Pending',
    'statusQualified': 'Qualified',
    'joined': 'Joined',
    'noReferrals': 'No referrals yet',
    'noReferralsBody': 'Share your code to start earning.',
    'noEarnings': 'No earnings yet',
    'noEarningsBody': 'Commission from your referrals will appear here.',
    'periodDaily': 'Daily',
    'periodWeekly': 'Weekly',
    'periodMonthly': 'Monthly',
    'periodAllTime': 'All time',
    'yourRank': 'Your rank',
    'notRanked': 'Not ranked yet',
    'points': 'pts',
    'noLeaderboard': 'No rankings yet',
    'noLeaderboardBody': 'Rankings for this period will appear here.',
    'coins': 'coins',
    'errorTitle': 'Something went wrong.',
    'loadMoreError': 'Could not load more. Tap to retry.',
    'noResults': 'No matches',
    'noResultsBody': 'Nothing matches your search.',
    'status_pending': 'Pending',
    'status_qualified': 'Qualified',
    'status_rejected': 'Rejected',
  };

  static const Map<String, String> _bn = {
    'title': 'রেফার করে আয়',
    'subtitle': 'বন্ধুদের আমন্ত্রণ জানান এবং তারা যোগ দিলে কয়েন আয় করুন।',
    'yourCode': 'আপনার রেফারেল কোড',
    'copyCode': 'কোড কপি করুন',
    'copyLink': 'লিঙ্ক কপি করুন',
    'copied': 'ক্লিপবোর্ডে কপি হয়েছে।',
    'totalReferrals': 'রেফারেল',
    'qualified': 'যোগ্য',
    'totalEarned': 'আয়',
    'commission': 'কমিশন',
    'haveACode': 'রেফারেল কোড আছে?',
    'enterCode': 'রেফারেল কোড লিখুন',
    'apply': 'প্রয়োগ করুন',
    'applying': 'প্রয়োগ হচ্ছে…',
    'applied': 'রেফারেল কোড প্রয়োগ হয়েছে!',
    'tabReferrals': 'রেফারেল',
    'tabEarnings': 'আয়',
    'tabLeaderboard': 'লিডারবোর্ড',
    'searchHint': 'খুঁজুন',
    'filterStatus': 'অবস্থা',
    'all': 'সব',
    'statusPending': 'বিচারাধীন',
    'statusQualified': 'যোগ্য',
    'joined': 'যোগ দিয়েছে',
    'noReferrals': 'এখনও কোনো রেফারেল নেই',
    'noReferralsBody': 'আয় শুরু করতে আপনার কোড শেয়ার করুন।',
    'noEarnings': 'এখনও কোনো আয় নেই',
    'noEarningsBody': 'আপনার রেফারেল থেকে কমিশন এখানে দেখা যাবে।',
    'periodDaily': 'দৈনিক',
    'periodWeekly': 'সাপ্তাহিক',
    'periodMonthly': 'মাসিক',
    'periodAllTime': 'সর্বকালীন',
    'yourRank': 'আপনার অবস্থান',
    'notRanked': 'এখনও র‍্যাঙ্ক হয়নি',
    'points': 'পয়েন্ট',
    'noLeaderboard': 'এখনও কোনো র‍্যাঙ্কিং নেই',
    'noLeaderboardBody': 'এই সময়ের র‍্যাঙ্কিং এখানে দেখা যাবে।',
    'coins': 'কয়েন',
    'errorTitle': 'কিছু ভুল হয়েছে।',
    'loadMoreError': 'আরও লোড করা যায়নি। আবার চেষ্টা করতে ট্যাপ করুন।',
    'noResults': 'কোনো মিল নেই',
    'noResultsBody': 'আপনার অনুসন্ধানের সাথে কিছু মেলেনি।',
    'status_pending': 'বিচারাধীন',
    'status_qualified': 'যোগ্য',
    'status_rejected': 'প্রত্যাখ্যাত',
  };
}

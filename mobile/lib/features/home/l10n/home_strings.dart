import 'package:flutter/widgets.dart';

/// Feature-scoped localization for the home module (English + Bangla).
class HomeStrings {
  const HomeStrings(this._values);

  final Map<String, String> _values;

  static HomeStrings of(BuildContext context) {
    final code = Localizations.localeOf(context).languageCode;
    return HomeStrings(code == 'bn' ? _bn : _en);
  }

  String _t(String key) => _values[key] ?? key;

  String get greeting => _t('greeting');
  String get guest => _t('guest');
  String get balanceTitle => _t('balanceTitle');
  String get availableLabel => _t('availableLabel');
  String get reservedLabel => _t('reservedLabel');
  String get coins => _t('coins');
  String get viewAll => _t('viewAll');
  String get quickActions => _t('quickActions');
  String get recentTransactions => _t('recentTransactions');
  String get noTransactions => _t('noTransactions');
  String get dailyCheckinTitle => _t('dailyCheckinTitle');
  String get dailyCheckinSubtitle => _t('dailyCheckinSubtitle');
  String get claim => _t('claim');
  String get scratchTitle => _t('scratchTitle');
  String get scratchSubtitle => _t('scratchSubtitle');
  String get spinTitle => _t('spinTitle');
  String get spinSubtitle => _t('spinSubtitle');
  String get offerwallTitle => _t('offerwallTitle');
  String get offerwallSubtitle => _t('offerwallSubtitle');
  String get tasksTitle => _t('tasksTitle');
  String get tasksSubtitle => _t('tasksSubtitle');
  String get referralTitle => _t('referralTitle');
  String get referralSubtitle => _t('referralSubtitle');
  String get invite => _t('invite');
  String get leaderboardTitle => _t('leaderboardTitle');
  String get leaderboardSubtitle => _t('leaderboardSubtitle');
  String get view => _t('view');
  String get open => _t('open');
  String get errorTitle => _t('errorTitle');
  String get comingSoon => _t('comingSoon');

  String actionLabel(String key) => _values['action_$key'] ?? key;

  static const Map<String, String> _en = {
    'greeting': 'Hi',
    'guest': 'there',
    'balanceTitle': 'Your balance',
    'availableLabel': 'Available',
    'reservedLabel': 'Reserved',
    'coins': 'coins',
    'viewAll': 'View all',
    'quickActions': 'Quick actions',
    'recentTransactions': 'Recent activity',
    'noTransactions': 'No transactions yet.',
    'dailyCheckinTitle': 'Daily check-in',
    'dailyCheckinSubtitle': 'Claim your daily bonus coins.',
    'claim': 'Claim',
    'scratchTitle': 'Scratch card',
    'scratchSubtitle': 'Scratch to reveal a reward.',
    'spinTitle': 'Spin the wheel',
    'spinSubtitle': 'Spin for a chance to win coins.',
    'offerwallTitle': 'Offerwall',
    'offerwallSubtitle': 'Complete offers to earn more.',
    'tasksTitle': 'Tasks',
    'tasksSubtitle': 'Finish tasks and get rewarded.',
    'referralTitle': 'Invite friends',
    'referralSubtitle': 'Earn coins for every friend who joins.',
    'invite': 'Invite',
    'leaderboardTitle': 'Leaderboard',
    'leaderboardSubtitle': 'See how you rank this week.',
    'view': 'View',
    'open': 'Open',
    'errorTitle': 'Could not load your dashboard.',
    'comingSoon': 'Coming soon',
    'action_earn': 'Earn',
    'action_spin': 'Spin',
    'action_scratch': 'Scratch',
    'action_checkin': 'Check-in',
    'action_offers': 'Offers',
    'action_tasks': 'Tasks',
    'action_refer': 'Refer',
    'action_wallet': 'Wallet',
    'action_withdraw': 'Withdraw',
    'action_leaderboard': 'Ranks',
  };

  static const Map<String, String> _bn = {
    'greeting': 'হ্যালো',
    'guest': 'বন্ধু',
    'balanceTitle': 'আপনার ব্যালেন্স',
    'availableLabel': 'উপলব্ধ',
    'reservedLabel': 'সংরক্ষিত',
    'coins': 'কয়েন',
    'viewAll': 'সব দেখুন',
    'quickActions': 'দ্রুত অ্যাকশন',
    'recentTransactions': 'সাম্প্রতিক কার্যকলাপ',
    'noTransactions': 'এখনও কোনো লেনদেন নেই।',
    'dailyCheckinTitle': 'দৈনিক চেক-ইন',
    'dailyCheckinSubtitle': 'আপনার দৈনিক বোনাস কয়েন দাবি করুন।',
    'claim': 'দাবি করুন',
    'scratchTitle': 'স্ক্র্যাচ কার্ড',
    'scratchSubtitle': 'পুরস্কার দেখতে স্ক্র্যাচ করুন।',
    'spinTitle': 'হুইল ঘোরান',
    'spinSubtitle': 'কয়েন জিততে ঘোরান।',
    'offerwallTitle': 'অফারওয়াল',
    'offerwallSubtitle': 'বেশি আয় করতে অফার সম্পূর্ণ করুন।',
    'tasksTitle': 'টাস্ক',
    'tasksSubtitle': 'টাস্ক শেষ করে পুরস্কার নিন।',
    'referralTitle': 'বন্ধুদের আমন্ত্রণ জানান',
    'referralSubtitle': 'প্রতিটি বন্ধুর জন্য কয়েন আয় করুন।',
    'invite': 'আমন্ত্রণ',
    'leaderboardTitle': 'লিডারবোর্ড',
    'leaderboardSubtitle': 'এই সপ্তাহে আপনার অবস্থান দেখুন।',
    'view': 'দেখুন',
    'open': 'খুলুন',
    'errorTitle': 'ড্যাশবোর্ড লোড করা যায়নি।',
    'comingSoon': 'শীঘ্রই আসছে',
    'action_earn': 'আয়',
    'action_spin': 'স্পিন',
    'action_scratch': 'স্ক্র্যাচ',
    'action_checkin': 'চেক-ইন',
    'action_offers': 'অফার',
    'action_tasks': 'টাস্ক',
    'action_refer': 'রেফার',
    'action_wallet': 'ওয়ালেট',
    'action_withdraw': 'উত্তোলন',
    'action_leaderboard': 'র‍্যাংক',
  };
}

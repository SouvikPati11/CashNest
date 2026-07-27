import 'package:flutter/widgets.dart';

/// Feature-scoped localization for the rewards module (English + Bangla).
class RewardsStrings {
  const RewardsStrings(this._values, this._isBn);

  final Map<String, String> _values;
  final bool _isBn;

  static RewardsStrings of(BuildContext context) {
    final isBn = Localizations.localeOf(context).languageCode == 'bn';
    return RewardsStrings(isBn ? _bn : _en, isBn);
  }

  String _t(String key) => _values[key] ?? key;

  // Hub
  String get title => _t('title');
  String get subtitle => _t('subtitle');
  String get dailyCheckin => _t('dailyCheckin');
  String get scratchCards => _t('scratchCards');
  String get spinWheel => _t('spinWheel');
  String get tasks => _t('tasks');
  String get rewardHistory => _t('rewardHistory');

  // Check-in
  String get checkinSubtitle => _t('checkinSubtitle');
  String get claimNow => _t('claimNow');
  String get claiming => _t('claiming');
  String get claimedToday => _t('claimedToday');
  String get comeBackTomorrow => _t('comeBackTomorrow');
  String get nextReward => _t('nextReward');
  String get rewardLadder => _t('rewardLadder');

  // Scratch
  String get scratchSubtitle => _t('scratchSubtitle');
  String get scratchToReveal => _t('scratchToReveal');
  String get tapToClaim => _t('tapToClaim');
  String get revealing => _t('revealing');
  String get noCards => _t('noCards');
  String get noCardsBody => _t('noCardsBody');

  // Spin
  String get spinSubtitle => _t('spinSubtitle');
  String get spinNow => _t('spinNow');
  String get spinning => _t('spinning');
  String get noSpins => _t('noSpins');
  String get noSpinsBody => _t('noSpinsBody');
  String get betterLuck => _t('betterLuck');

  // Tasks
  String get tasksSubtitle => _t('tasksSubtitle');
  String get start => _t('start');
  String get complete => _t('complete');
  String get completed => _t('completed');
  String get taskPending => _t('taskPending');
  String get noTasks => _t('noTasks');
  String get noTasksBody => _t('noTasksBody');

  // History
  String get noHistory => _t('noHistory');
  String get noHistoryBody => _t('noHistoryBody');
  String kindLabel(String kind) => _t('kind_$kind');

  // Reward result
  String get congrats => _t('congrats');
  String get youEarned => _t('youEarned');
  String get awesome => _t('awesome');

  // Common
  String get coins => _t('coins');
  String get errorTitle => _t('errorTitle');
  String get close => _t('close');

  /// "{n}-day streak".
  String streak(int days) => _isBn ? '$days দিনের ধারা' : '$days-day streak';

  /// "{n} spins left".
  String spinsLeft(int n) => _isBn ? '$n স্পিন বাকি' : '$n spins left';

  /// "Day {n}".
  String dayLabel(int day) => _isBn ? 'দিন $day' : 'Day $day';

  static const Map<String, String> _en = {
    'title': 'Rewards',
    'subtitle': 'Earn coins every day',
    'dailyCheckin': 'Daily check-in',
    'scratchCards': 'Scratch cards',
    'spinWheel': 'Spin the wheel',
    'tasks': 'Tasks',
    'rewardHistory': 'Reward history',
    'checkinSubtitle': 'Claim your daily bonus and keep your streak alive.',
    'claimNow': 'Claim now',
    'claiming': 'Claiming…',
    'claimedToday': 'Claimed for today',
    'comeBackTomorrow': 'Come back tomorrow for more coins.',
    'nextReward': 'Next reward',
    'rewardLadder': 'Reward ladder',
    'scratchSubtitle': 'Scratch to reveal your prize.',
    'scratchToReveal': 'Scratch here to reveal',
    'tapToClaim': 'Tap to claim',
    'revealing': 'Revealing…',
    'noCards': 'No scratch cards',
    'noCardsBody': 'Earn cards by completing activities. Check back soon.',
    'spinSubtitle': 'Spin for a chance to win coins.',
    'spinNow': 'Spin now',
    'spinning': 'Spinning…',
    'noSpins': 'No spins left',
    'noSpinsBody': 'You have used all your spins today. Come back tomorrow.',
    'betterLuck': 'Better luck next time!',
    'tasksSubtitle': 'Complete tasks to earn more coins.',
    'start': 'Start',
    'complete': 'Complete',
    'completed': 'Completed',
    'taskPending': 'Submitted — pending review.',
    'noTasks': 'No tasks available',
    'noTasksBody': 'New tasks arrive regularly. Check back soon.',
    'noHistory': 'No rewards yet',
    'noHistoryBody': 'Your claimed rewards will appear here.',
    'kind_checkin': 'Daily check-in',
    'kind_scratch': 'Scratch card',
    'kind_spin': 'Spin wheel',
    'kind_task': 'Task',
    'congrats': 'Congratulations!',
    'youEarned': 'You earned',
    'awesome': 'Awesome!',
    'coins': 'coins',
    'errorTitle': 'Something went wrong.',
    'close': 'Close',
  };

  static const Map<String, String> _bn = {
    'title': 'পুরস্কার',
    'subtitle': 'প্রতিদিন কয়েন আয় করুন',
    'dailyCheckin': 'দৈনিক চেক-ইন',
    'scratchCards': 'স্ক্র্যাচ কার্ড',
    'spinWheel': 'হুইল ঘোরান',
    'tasks': 'টাস্ক',
    'rewardHistory': 'পুরস্কারের ইতিহাস',
    'checkinSubtitle': 'আপনার দৈনিক বোনাস নিন এবং ধারা বজায় রাখুন।',
    'claimNow': 'এখন নিন',
    'claiming': 'নেওয়া হচ্ছে…',
    'claimedToday': 'আজকের জন্য নেওয়া হয়েছে',
    'comeBackTomorrow': 'আরও কয়েনের জন্য আগামীকাল ফিরে আসুন।',
    'nextReward': 'পরবর্তী পুরস্কার',
    'rewardLadder': 'পুরস্কারের সিঁড়ি',
    'scratchSubtitle': 'পুরস্কার দেখতে স্ক্র্যাচ করুন।',
    'scratchToReveal': 'দেখতে এখানে স্ক্র্যাচ করুন',
    'tapToClaim': 'নিতে ট্যাপ করুন',
    'revealing': 'দেখানো হচ্ছে…',
    'noCards': 'কোনো স্ক্র্যাচ কার্ড নেই',
    'noCardsBody': 'কার্যকলাপ সম্পন্ন করে কার্ড অর্জন করুন। শীঘ্রই দেখুন।',
    'spinSubtitle': 'কয়েন জিততে ঘোরান।',
    'spinNow': 'এখন ঘোরান',
    'spinning': 'ঘুরছে…',
    'noSpins': 'কোনো স্পিন বাকি নেই',
    'noSpinsBody': 'আপনি আজকের সব স্পিন ব্যবহার করেছেন। আগামীকাল ফিরে আসুন।',
    'betterLuck': 'পরের বার শুভকামনা!',
    'tasksSubtitle': 'আরও কয়েন আয় করতে টাস্ক সম্পূর্ণ করুন।',
    'start': 'শুরু',
    'complete': 'সম্পূর্ণ',
    'completed': 'সম্পন্ন',
    'taskPending': 'জমা দেওয়া হয়েছে — পর্যালোচনার অপেক্ষায়।',
    'noTasks': 'কোনো টাস্ক নেই',
    'noTasksBody': 'নিয়মিত নতুন টাস্ক আসে। শীঘ্রই দেখুন।',
    'noHistory': 'এখনও কোনো পুরস্কার নেই',
    'noHistoryBody': 'আপনার নেওয়া পুরস্কার এখানে দেখা যাবে।',
    'kind_checkin': 'দৈনিক চেক-ইন',
    'kind_scratch': 'স্ক্র্যাচ কার্ড',
    'kind_spin': 'স্পিন হুইল',
    'kind_task': 'টাস্ক',
    'congrats': 'অভিনন্দন!',
    'youEarned': 'আপনি পেয়েছেন',
    'awesome': 'দারুণ!',
    'coins': 'কয়েন',
    'errorTitle': 'কিছু ভুল হয়েছে।',
    'close': 'বন্ধ করুন',
  };
}

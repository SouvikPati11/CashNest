import 'package:flutter/widgets.dart';

/// Feature-scoped localization for the notification feature (English + Bangla).
class NotificationStrings {
  const NotificationStrings(this._values);

  final Map<String, String> _values;

  static NotificationStrings of(BuildContext context) {
    final code = Localizations.localeOf(context).languageCode;
    return NotificationStrings(code == 'bn' ? _bn : _en);
  }

  String _t(String key) => _values[key] ?? key;

  String get title => _t('title');
  String get all => _t('all');
  String get unread => _t('unread');
  String get markAllRead => _t('markAllRead');
  String get allMarkedRead => _t('allMarkedRead');
  String get noNotifications => _t('noNotifications');
  String get noNotificationsBody => _t('noNotificationsBody');
  String get noUnread => _t('noUnread');
  String get noUnreadBody => _t('noUnreadBody');
  String get detailTitle => _t('detailTitle');
  String get openLink => _t('openLink');
  String get linkCopied => _t('linkCopied');
  String get preferences => _t('preferences');
  String get pushEnabled => _t('pushEnabled');
  String get pushEnabledDesc => _t('pushEnabledDesc');
  String get transactional => _t('transactional');
  String get transactionalDesc => _t('transactionalDesc');
  String get promotional => _t('promotional');
  String get promotionalDesc => _t('promotionalDesc');
  String get errorTitle => _t('errorTitle');
  String get loadMoreError => _t('loadMoreError');
  String get noResults => _t('noResults');
  String get noResultsBody => _t('noResultsBody');

  static const Map<String, String> _en = {
    'title': 'Notifications',
    'all': 'All',
    'unread': 'Unread',
    'markAllRead': 'Mark all read',
    'allMarkedRead': 'All notifications marked read.',
    'noNotifications': 'No notifications',
    'noNotificationsBody': 'You are all caught up.',
    'noUnread': 'No unread notifications',
    'noUnreadBody': 'You have read everything.',
    'detailTitle': 'Notification',
    'openLink': 'Open link',
    'linkCopied': 'Link copied to clipboard.',
    'preferences': 'Preferences',
    'pushEnabled': 'Push notifications',
    'pushEnabledDesc': 'Receive push notifications on this device.',
    'transactional': 'Transactional',
    'transactionalDesc': 'Withdrawals, rewards, and account activity.',
    'promotional': 'Promotional',
    'promotionalDesc': 'Offers, bonuses, and announcements.',
    'errorTitle': 'Could not load notifications.',
    'loadMoreError': 'Could not load more. Tap to retry.',
    'noResults': 'Nothing here',
    'noResultsBody': 'No notifications match this filter.',
  };

  static const Map<String, String> _bn = {
    'title': 'বিজ্ঞপ্তি',
    'all': 'সব',
    'unread': 'অপঠিত',
    'markAllRead': 'সব পঠিত করুন',
    'allMarkedRead': 'সব বিজ্ঞপ্তি পঠিত হিসেবে চিহ্নিত হয়েছে।',
    'noNotifications': 'কোনো বিজ্ঞপ্তি নেই',
    'noNotificationsBody': 'আপনি সব দেখে নিয়েছেন।',
    'noUnread': 'কোনো অপঠিত বিজ্ঞপ্তি নেই',
    'noUnreadBody': 'আপনি সব পড়ে ফেলেছেন।',
    'detailTitle': 'বিজ্ঞপ্তি',
    'openLink': 'লিঙ্ক খুলুন',
    'linkCopied': 'লিঙ্ক ক্লিপবোর্ডে কপি হয়েছে।',
    'preferences': 'পছন্দসমূহ',
    'pushEnabled': 'পুশ বিজ্ঞপ্তি',
    'pushEnabledDesc': 'এই ডিভাইসে পুশ বিজ্ঞপ্তি পান।',
    'transactional': 'লেনদেন সংক্রান্ত',
    'transactionalDesc': 'উত্তোলন, পুরস্কার এবং অ্যাকাউন্ট কার্যকলাপ।',
    'promotional': 'প্রচারমূলক',
    'promotionalDesc': 'অফার, বোনাস এবং ঘোষণা।',
    'errorTitle': 'বিজ্ঞপ্তি লোড করা যায়নি।',
    'loadMoreError': 'আরও লোড করা যায়নি। আবার চেষ্টা করতে ট্যাপ করুন।',
    'noResults': 'এখানে কিছু নেই',
    'noResultsBody': 'এই ফিল্টারের সাথে কোনো বিজ্ঞপ্তি মেলেনি।',
  };
}

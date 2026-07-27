import 'package:flutter/widgets.dart';

/// Feature-scoped localization for the settings feature (English + Bangla).
class SettingsStrings {
  const SettingsStrings(this._values);

  final Map<String, String> _values;

  static SettingsStrings of(BuildContext context) {
    final code = Localizations.localeOf(context).languageCode;
    return SettingsStrings(code == 'bn' ? _bn : _en);
  }

  String _t(String key) => _values[key] ?? key;

  String get title => _t('title');
  String get accountSection => _t('accountSection');
  String get preferencesSection => _t('preferencesSection');
  String get aboutSection => _t('aboutSection');

  String get profile => _t('profile');
  String get editProfile => _t('editProfile');
  String get language => _t('language');
  String get theme => _t('theme');
  String get notificationSettings => _t('notificationSettings');
  String get about => _t('about');
  String get privacyPolicy => _t('privacyPolicy');
  String get terms => _t('terms');
  String get faq => _t('faq');
  String get support => _t('support');
  String get appVersion => _t('appVersion');
  String get logout => _t('logout');

  String get name => _t('name');
  String get email => _t('email');
  String get country => _t('country');
  String get countryHint => _t('countryHint');
  String get referralCode => _t('referralCode');
  String get memberSince => _t('memberSince');
  String get kycStatus => _t('kycStatus');
  String get save => _t('save');
  String get saving => _t('saving');
  String get saved => _t('saved');
  String get nameTooShort => _t('nameTooShort');

  String get systemTheme => _t('systemTheme');
  String get lightTheme => _t('lightTheme');
  String get darkTheme => _t('darkTheme');

  String get logoutConfirmTitle => _t('logoutConfirmTitle');
  String get logoutConfirmBody => _t('logoutConfirmBody');
  String get cancel => _t('cancel');

  String get supportTitle => _t('supportTitle');
  String get newTicket => _t('newTicket');
  String get subject => _t('subject');
  String get message => _t('message');
  String get send => _t('send');
  String get sending => _t('sending');
  String get reply => _t('reply');
  String get typeReply => _t('typeReply');
  String get ticketClosed => _t('ticketClosed');
  String get noTickets => _t('noTickets');
  String get noTicketsBody => _t('noTicketsBody');
  String get all => _t('all');
  String get open => _t('open');
  String get closed => _t('closed');

  String get noFaqs => _t('noFaqs');
  String get noFaqsBody => _t('noFaqsBody');
  String get updateAvailable => _t('updateAvailable');
  String get upToDate => _t('upToDate');
  String get forceUpdate => _t('forceUpdate');
  String get copyStoreLink => _t('copyStoreLink');
  String get linkCopied => _t('linkCopied');
  String get installedVersion => _t('installedVersion');
  String get latestVersion => _t('latestVersion');
  String get changelog => _t('changelog');

  String get errorTitle => _t('errorTitle');
  String get loadMoreError => _t('loadMoreError');
  String get noResults => _t('noResults');
  String get noResultsBody => _t('noResultsBody');

  static const Map<String, String> _en = {
    'title': 'Settings',
    'accountSection': 'Account',
    'preferencesSection': 'Preferences',
    'aboutSection': 'About & support',
    'profile': 'Profile',
    'editProfile': 'Edit profile',
    'language': 'Language',
    'theme': 'Theme',
    'notificationSettings': 'Notification settings',
    'about': 'About',
    'privacyPolicy': 'Privacy policy',
    'terms': 'Terms of service',
    'faq': 'FAQ',
    'support': 'Help & support',
    'appVersion': 'App version',
    'logout': 'Log out',
    'name': 'Name',
    'email': 'Email',
    'country': 'Country',
    'countryHint': 'ISO code (e.g. IN)',
    'referralCode': 'Referral code',
    'memberSince': 'Member since',
    'kycStatus': 'KYC status',
    'save': 'Save',
    'saving': 'Saving…',
    'saved': 'Profile updated.',
    'nameTooShort': 'Name must be at least 2 characters.',
    'systemTheme': 'System default',
    'lightTheme': 'Light',
    'darkTheme': 'Dark',
    'logoutConfirmTitle': 'Log out?',
    'logoutConfirmBody': 'You will need to sign in again to use the app.',
    'cancel': 'Cancel',
    'supportTitle': 'Help & support',
    'newTicket': 'New ticket',
    'subject': 'Subject',
    'message': 'Message',
    'send': 'Send',
    'sending': 'Sending…',
    'reply': 'Reply',
    'typeReply': 'Type your reply',
    'ticketClosed': 'This ticket is closed.',
    'noTickets': 'No support tickets',
    'noTicketsBody': 'Create a ticket and our team will help you.',
    'all': 'All',
    'open': 'Open',
    'closed': 'Closed',
    'noFaqs': 'No FAQs',
    'noFaqsBody': 'Frequently asked questions will appear here.',
    'updateAvailable': 'Update available',
    'upToDate': 'You are up to date',
    'forceUpdate': 'This version is no longer supported. Please update.',
    'copyStoreLink': 'Copy store link',
    'linkCopied': 'Store link copied to clipboard.',
    'installedVersion': 'Installed',
    'latestVersion': 'Latest',
    'changelog': "What's new",
    'errorTitle': 'Something went wrong.',
    'loadMoreError': 'Could not load more. Tap to retry.',
    'noResults': 'No matches',
    'noResultsBody': 'Nothing matches your search.',
  };

  static const Map<String, String> _bn = {
    'title': 'সেটিংস',
    'accountSection': 'অ্যাকাউন্ট',
    'preferencesSection': 'পছন্দসমূহ',
    'aboutSection': 'সম্পর্কে ও সহায়তা',
    'profile': 'প্রোফাইল',
    'editProfile': 'প্রোফাইল সম্পাদনা',
    'language': 'ভাষা',
    'theme': 'থিম',
    'notificationSettings': 'বিজ্ঞপ্তি সেটিংস',
    'about': 'সম্পর্কে',
    'privacyPolicy': 'গোপনীয়তা নীতি',
    'terms': 'সেবার শর্তাবলী',
    'faq': 'সাধারণ প্রশ্ন',
    'support': 'সহায়তা',
    'appVersion': 'অ্যাপ সংস্করণ',
    'logout': 'লগ আউট',
    'name': 'নাম',
    'email': 'ইমেইল',
    'country': 'দেশ',
    'countryHint': 'আইএসও কোড (যেমন IN)',
    'referralCode': 'রেফারেল কোড',
    'memberSince': 'সদস্য যেদিন থেকে',
    'kycStatus': 'কেওয়াইসি অবস্থা',
    'save': 'সংরক্ষণ',
    'saving': 'সংরক্ষণ হচ্ছে…',
    'saved': 'প্রোফাইল আপডেট হয়েছে।',
    'nameTooShort': 'নাম কমপক্ষে ২ অক্ষরের হতে হবে।',
    'systemTheme': 'সিস্টেম ডিফল্ট',
    'lightTheme': 'লাইট',
    'darkTheme': 'ডার্ক',
    'logoutConfirmTitle': 'লগ আউট?',
    'logoutConfirmBody': 'অ্যাপ ব্যবহার করতে আবার সাইন ইন করতে হবে।',
    'cancel': 'বাতিল',
    'supportTitle': 'সহায়তা',
    'newTicket': 'নতুন টিকিট',
    'subject': 'বিষয়',
    'message': 'বার্তা',
    'send': 'পাঠান',
    'sending': 'পাঠানো হচ্ছে…',
    'reply': 'উত্তর',
    'typeReply': 'আপনার উত্তর লিখুন',
    'ticketClosed': 'এই টিকিটটি বন্ধ।',
    'noTickets': 'কোনো সহায়তা টিকিট নেই',
    'noTicketsBody': 'একটি টিকিট তৈরি করুন, আমাদের দল সাহায্য করবে।',
    'all': 'সব',
    'open': 'খোলা',
    'closed': 'বন্ধ',
    'noFaqs': 'কোনো প্রশ্ন নেই',
    'noFaqsBody': 'সাধারণ জিজ্ঞাসিত প্রশ্ন এখানে দেখা যাবে।',
    'updateAvailable': 'আপডেট উপলব্ধ',
    'upToDate': 'আপনি হালনাগাদ আছেন',
    'forceUpdate': 'এই সংস্করণটি আর সমর্থিত নয়। অনুগ্রহ করে আপডেট করুন।',
    'copyStoreLink': 'স্টোর লিঙ্ক কপি করুন',
    'linkCopied': 'স্টোর লিঙ্ক ক্লিপবোর্ডে কপি হয়েছে।',
    'installedVersion': 'ইনস্টলড',
    'latestVersion': 'সর্বশেষ',
    'changelog': 'নতুন কী আছে',
    'errorTitle': 'কিছু ভুল হয়েছে।',
    'loadMoreError': 'আরও লোড করা যায়নি। আবার চেষ্টা করতে ট্যাপ করুন।',
    'noResults': 'কোনো মিল নেই',
    'noResultsBody': 'আপনার অনুসন্ধানের সাথে কিছু মেলেনি।',
  };
}

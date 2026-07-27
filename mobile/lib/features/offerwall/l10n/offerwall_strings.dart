import 'package:flutter/widgets.dart';

/// Feature-scoped localization for the offerwall + CPA feature (English + Bangla).
class OfferwallStrings {
  const OfferwallStrings(this._values);

  final Map<String, String> _values;

  static OfferwallStrings of(BuildContext context) {
    final code = Localizations.localeOf(context).languageCode;
    return OfferwallStrings(code == 'bn' ? _bn : _en);
  }

  String _t(String key) => _values[key] ?? key;

  String get title => _t('title');
  String get tabOffers => _t('tabOffers');
  String get tabCpa => _t('tabCpa');
  String get tabHistory => _t('tabHistory');
  String get searchHint => _t('searchHint');
  String get filters => _t('filters');
  String get provider => _t('provider');
  String get category => _t('category');
  String get all => _t('all');
  String get apply => _t('apply');
  String get clear => _t('clear');
  String get clearFilters => _t('clearFilters');
  String get open => _t('open');
  String get opening => _t('opening');
  String get offerDetails => _t('offerDetails');
  String get goal => _t('goal');
  String get reward => _t('reward');
  String get coins => _t('coins');
  String get noOffers => _t('noOffers');
  String get noOffersBody => _t('noOffersBody');
  String get noResults => _t('noResults');
  String get noResultsBody => _t('noResultsBody');
  String get noHistory => _t('noHistory');
  String get noHistoryBody => _t('noHistoryBody');
  String get errorTitle => _t('errorTitle');
  String get loadMoreError => _t('loadMoreError');
  String get redirectReady => _t('redirectReady');
  String get copyLink => _t('copyLink');
  String get linkCopied => _t('linkCopied');

  static const Map<String, String> _en = {
    'title': 'Earn',
    'tabOffers': 'Offers',
    'tabCpa': 'CPA',
    'tabHistory': 'History',
    'searchHint': 'Search offers',
    'filters': 'Filters',
    'provider': 'Provider',
    'category': 'Category',
    'all': 'All',
    'apply': 'Apply',
    'clear': 'Clear',
    'clearFilters': 'Clear filters',
    'open': 'Start offer',
    'opening': 'Opening…',
    'offerDetails': 'Offer details',
    'goal': 'Goal',
    'reward': 'Reward',
    'coins': 'coins',
    'noOffers': 'No offers available',
    'noOffersBody': 'New offers arrive regularly. Check back soon.',
    'noResults': 'No matches',
    'noResultsBody': 'No offers match your search or filters.',
    'noHistory': 'No completed offers',
    'noHistoryBody': 'Rewards from completed offers will appear here.',
    'errorTitle': 'Could not load offers.',
    'loadMoreError': 'Could not load more. Tap to retry.',
    'redirectReady': 'Your offer link is ready.',
    'copyLink': 'Copy link',
    'linkCopied': 'Link copied to clipboard.',
  };

  static const Map<String, String> _bn = {
    'title': 'আয় করুন',
    'tabOffers': 'অফার',
    'tabCpa': 'সিপিএ',
    'tabHistory': 'ইতিহাস',
    'searchHint': 'অফার খুঁজুন',
    'filters': 'ফিল্টার',
    'provider': 'প্রোভাইডার',
    'category': 'বিভাগ',
    'all': 'সব',
    'apply': 'প্রয়োগ করুন',
    'clear': 'মুছুন',
    'clearFilters': 'ফিল্টার মুছুন',
    'open': 'অফার শুরু করুন',
    'opening': 'খোলা হচ্ছে…',
    'offerDetails': 'অফারের বিবরণ',
    'goal': 'লক্ষ্য',
    'reward': 'পুরস্কার',
    'coins': 'কয়েন',
    'noOffers': 'কোনো অফার নেই',
    'noOffersBody': 'নিয়মিত নতুন অফার আসে। শীঘ্রই দেখুন।',
    'noResults': 'কোনো মিল নেই',
    'noResultsBody': 'আপনার অনুসন্ধান বা ফিল্টারের সাথে কোনো অফার মেলেনি।',
    'noHistory': 'কোনো সম্পন্ন অফার নেই',
    'noHistoryBody': 'সম্পন্ন অফারের পুরস্কার এখানে দেখা যাবে।',
    'errorTitle': 'অফার লোড করা যায়নি।',
    'loadMoreError': 'আরও লোড করা যায়নি। আবার চেষ্টা করতে ট্যাপ করুন।',
    'redirectReady': 'আপনার অফার লিঙ্ক প্রস্তুত।',
    'copyLink': 'লিঙ্ক কপি করুন',
    'linkCopied': 'লিঙ্ক ক্লিপবোর্ডে কপি হয়েছে।',
  };
}

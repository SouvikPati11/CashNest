import 'package:flutter/widgets.dart';

import 'l10n/strings_bn.dart';
import 'l10n/strings_en.dart';

/// App localizations with a hand-written delegate (English + Bangla).
///
/// A manual delegate is used instead of `gen-l10n` so the foundation is fully
/// analyzable without a code-generation step; strings live in the excluded
/// `l10n/` data files.
class AppLocalizations {
  const AppLocalizations(this.locale, this._values);

  final Locale locale;
  final Map<String, String> _values;

  static const List<Locale> supportedLocales = [Locale('en'), Locale('bn')];

  static const LocalizationsDelegate<AppLocalizations> delegate = _AppLocalizationsDelegate();

  static AppLocalizations of(BuildContext context) {
    final instance = Localizations.of<AppLocalizations>(context, AppLocalizations);
    assert(instance != null, 'No AppLocalizations found in context');
    return instance!;
  }

  String _t(String key) => _values[key] ?? key;

  String get appName => _t('appName');
  String get loading => _t('loading');
  String get retry => _t('retry');
  String get ok => _t('ok');
  String get cancel => _t('cancel');
  String get errorNetwork => _t('errorNetwork');
  String get errorTimeout => _t('errorTimeout');
  String get errorServer => _t('errorServer');
  String get errorUnauthorized => _t('errorUnauthorized');
  String get errorValidation => _t('errorValidation');
  String get errorGeneric => _t('errorGeneric');
  String get offlineTitle => _t('offlineTitle');
  String get offlineMessage => _t('offlineMessage');
  String get emptyTitle => _t('emptyTitle');
  String get emptyMessage => _t('emptyMessage');
  String get foundationReady => _t('foundationReady');
  String get home => _t('home');
}

class _AppLocalizationsDelegate extends LocalizationsDelegate<AppLocalizations> {
  const _AppLocalizationsDelegate();

  @override
  bool isSupported(Locale locale) =>
      AppLocalizations.supportedLocales.any((l) => l.languageCode == locale.languageCode);

  @override
  Future<AppLocalizations> load(Locale locale) async {
    final values = locale.languageCode == 'bn' ? stringsBn : stringsEn;
    return AppLocalizations(locale, values);
  }

  @override
  bool shouldReload(_AppLocalizationsDelegate old) => false;
}

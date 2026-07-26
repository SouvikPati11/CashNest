import 'package:flutter/widgets.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../storage/preference_manager.dart';
import '../storage/storage_keys.dart';
import 'app_localizations.dart';

/// Persists and exposes the active [Locale] for runtime language switching.
class LocaleController extends StateNotifier<Locale> {
  LocaleController(this._prefs) : super(_load(_prefs));

  final PreferenceManager _prefs;

  static Locale _load(PreferenceManager prefs) {
    final code = prefs.getString(StorageKeys.locale);
    for (final locale in AppLocalizations.supportedLocales) {
      if (locale.languageCode == code) {
        return locale;
      }
    }
    return const Locale('en');
  }

  Future<void> set(Locale locale) async {
    final supported = AppLocalizations.supportedLocales
        .any((l) => l.languageCode == locale.languageCode);
    if (!supported || locale == state) {
      return;
    }
    state = locale;
    await _prefs.setString(StorageKeys.locale, locale.languageCode);
  }
}

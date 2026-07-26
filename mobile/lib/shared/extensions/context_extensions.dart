import 'package:flutter/material.dart';

import '../../core/localization/app_localizations.dart';

/// Ergonomic accessors for theme and localization off [BuildContext].
extension AppContextX on BuildContext {
  ThemeData get theme => Theme.of(this);

  ColorScheme get colors => Theme.of(this).colorScheme;

  TextTheme get textTheme => Theme.of(this).textTheme;

  AppLocalizations get l10n => AppLocalizations.of(this);
}

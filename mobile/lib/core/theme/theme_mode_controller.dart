import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../storage/preference_manager.dart';
import '../storage/storage_keys.dart';

/// Persists and exposes the user's preferred [ThemeMode] for runtime switching.
class ThemeModeController extends StateNotifier<ThemeMode> {
  ThemeModeController(this._prefs) : super(_load(_prefs));

  final PreferenceManager _prefs;

  static ThemeMode _load(PreferenceManager prefs) {
    return switch (prefs.getString(StorageKeys.themeMode)) {
      'light' => ThemeMode.light,
      'dark' => ThemeMode.dark,
      'system' => ThemeMode.system,
      // Premium dark experience by default until the user picks otherwise.
      _ => ThemeMode.dark,
    };
  }

  Future<void> set(ThemeMode mode) async {
    if (mode == state) {
      return;
    }
    state = mode;
    await _prefs.setString(StorageKeys.themeMode, mode.name);
  }

  /// Whether the user has explicitly chosen a theme mode (vs. the default).
  bool get userHasChosen => _prefs.has(StorageKeys.themeMode);

  /// Applies the Admin-configured default theme mode, but only when the user has
  /// not explicitly chosen one. Not persisted, so a later admin change is picked
  /// up on the next launch. A no-op (graceful fallback) when [mode] is null.
  void applyServerDefault(ThemeMode? mode) {
    if (mode == null || userHasChosen || mode == state) {
      return;
    }
    state = mode;
  }

  Future<void> toggle() =>
      set(state == ThemeMode.dark ? ThemeMode.light : ThemeMode.dark);
}

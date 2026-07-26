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
      _ => ThemeMode.system,
    };
  }

  Future<void> set(ThemeMode mode) async {
    if (mode == state) {
      return;
    }
    state = mode;
    await _prefs.setString(StorageKeys.themeMode, mode.name);
  }

  Future<void> toggle() =>
      set(state == ThemeMode.dark ? ThemeMode.light : ThemeMode.dark);
}

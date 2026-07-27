import 'package:flutter/foundation.dart';

/// The language + theme preferences from `GET /v1/settings` (the notification
/// preference fields are owned by the Notification module and not surfaced here).
@immutable
class SettingsPreferences {
  const SettingsPreferences({this.language = 'en', this.themeMode = 'system'});

  final String language;

  /// `system` | `light` | `dark`.
  final String themeMode;

  factory SettingsPreferences.fromJson(Map<String, dynamic> json) {
    final prefs = json['preferences'];
    final map = prefs is Map<String, dynamic> ? prefs : json;
    return SettingsPreferences(
      language: map['language'] as String? ?? 'en',
      themeMode: map['theme_mode'] as String? ?? 'system',
    );
  }

  static const SettingsPreferences defaults = SettingsPreferences();
}

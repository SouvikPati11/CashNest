import 'package:flutter/foundation.dart';

/// Notification preferences, a subset of user settings preferences
/// (`GET/PUT /v1/settings`). This feature reads/writes only the notification
/// fields; the Settings module is not built or modified.
@immutable
class NotificationPreferences {
  const NotificationPreferences({
    this.pushEnabled = true,
    this.transactional = true,
    this.promotional = true,
  });

  final bool pushEnabled;
  final bool transactional;
  final bool promotional;

  factory NotificationPreferences.fromJson(Map<String, dynamic> json) {
    // Accepts either the `preferences` object or the top-level payload.
    final prefs = json['preferences'];
    final map = prefs is Map<String, dynamic> ? prefs : json;
    return NotificationPreferences(
      pushEnabled: map['notif_push_enabled'] as bool? ?? true,
      transactional: map['notif_transactional'] as bool? ?? true,
      promotional: map['notif_promotional'] as bool? ?? true,
    );
  }

  /// Only the notification fields, for the settings PUT body.
  Map<String, dynamic> toJson() => {
        'notif_push_enabled': pushEnabled,
        'notif_transactional': transactional,
        'notif_promotional': promotional,
      };

  NotificationPreferences copyWith({
    bool? pushEnabled,
    bool? transactional,
    bool? promotional,
  }) {
    return NotificationPreferences(
      pushEnabled: pushEnabled ?? this.pushEnabled,
      transactional: transactional ?? this.transactional,
      promotional: promotional ?? this.promotional,
    );
  }

  static const NotificationPreferences defaults = NotificationPreferences();
}

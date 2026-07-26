import 'package:flutter/foundation.dart';

/// An in-app announcement for the home popup (`GET /v1/announcements`).
@immutable
class HomeAnnouncement {
  const HomeAnnouncement({
    required this.id,
    required this.title,
    required this.body,
    this.displayType = 'popup',
    this.priority = 100,
    this.isDismissible = true,
    this.actionType = 'none',
    this.actionValue,
  });

  final int id;
  final String title;
  final String body;
  final String displayType;
  final int priority;
  final bool isDismissible;
  final String actionType;
  final String? actionValue;

  factory HomeAnnouncement.fromJson(Map<String, dynamic> json) {
    return HomeAnnouncement(
      id: (json['id'] as num?)?.toInt() ?? 0,
      title: json['title'] as String? ?? '',
      body: json['body'] as String? ?? '',
      displayType: json['display_type'] as String? ?? 'popup',
      priority: (json['priority'] as num?)?.toInt() ?? 100,
      isDismissible: json['is_dismissible'] as bool? ?? true,
      actionType: json['action_type'] as String? ?? 'none',
      actionValue: json['action_value'] as String?,
    );
  }
}

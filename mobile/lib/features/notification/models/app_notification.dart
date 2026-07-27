import 'package:flutter/foundation.dart';

/// An in-app notification (`GET /v1/notifications`).
@immutable
class AppNotification {
  const AppNotification({
    required this.uuid,
    required this.title,
    required this.body,
    this.type = 'system',
    this.deepLink,
    this.imageUrl,
    this.isRead = false,
    this.createdAt,
  });

  final String uuid;
  final String title;
  final String body;
  final String type;
  final String? deepLink;
  final String? imageUrl;
  final bool isRead;
  final DateTime? createdAt;

  static DateTime? _date(dynamic v) => v is String ? DateTime.tryParse(v) : null;

  factory AppNotification.fromJson(Map<String, dynamic> json) {
    return AppNotification(
      uuid: json['uuid'] as String? ?? '',
      title: json['title'] as String? ?? '',
      body: json['body'] as String? ?? '',
      type: json['type'] as String? ?? 'system',
      deepLink: json['deep_link'] as String?,
      imageUrl: json['image_url'] as String?,
      isRead: json['is_read'] as bool? ?? false,
      createdAt: _date(json['created_at']),
    );
  }

  AppNotification copyWith({bool? isRead}) {
    return AppNotification(
      uuid: uuid,
      title: title,
      body: body,
      type: type,
      deepLink: deepLink,
      imageUrl: imageUrl,
      isRead: isRead ?? this.isRead,
      createdAt: createdAt,
    );
  }
}

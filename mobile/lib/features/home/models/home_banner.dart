import 'package:flutter/foundation.dart';

/// A promotional banner for the home carousel (`GET /v1/banners`).
@immutable
class HomeBanner {
  const HomeBanner({
    required this.id,
    required this.imageUrl,
    this.title,
    this.actionType = 'none',
    this.actionValue,
    this.sortOrder = 0,
  });

  final int id;
  final String imageUrl;
  final String? title;
  final String actionType;
  final String? actionValue;
  final int sortOrder;

  factory HomeBanner.fromJson(Map<String, dynamic> json) {
    return HomeBanner(
      id: (json['id'] as num?)?.toInt() ?? 0,
      imageUrl: json['image_url'] as String? ?? '',
      title: json['title'] as String?,
      actionType: json['action_type'] as String? ?? 'none',
      actionValue: json['action_value'] as String?,
      sortOrder: (json['sort_order'] as num?)?.toInt() ?? 0,
    );
  }
}

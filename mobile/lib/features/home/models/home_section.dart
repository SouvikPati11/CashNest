import 'package:flutter/foundation.dart';

/// A server-driven home layout section (`GET /v1/home/layout`).
///
/// `type` is kept as a raw string so unknown/newer section types are ignored
/// gracefully by the renderer (forward-compatible).
@immutable
class HomeSection {
  const HomeSection({
    required this.type,
    required this.sortOrder,
    this.title,
    this.config = const {},
  });

  final String type;
  final int sortOrder;
  final String? title;
  final Map<String, dynamic> config;

  factory HomeSection.fromJson(Map<String, dynamic> json) {
    final config = json['config'];
    return HomeSection(
      type: json['type'] as String? ?? '',
      sortOrder: (json['sort_order'] as num?)?.toInt() ?? 0,
      title: json['title'] as String?,
      config: config is Map<String, dynamic> ? config : const {},
    );
  }

  /// Reads a string list from the section config (e.g. quick-action keys).
  List<String> stringList(String key) {
    final value = config[key];
    if (value is List) {
      return value.whereType<Object>().map((e) => e.toString()).toList();
    }
    return const [];
  }
}

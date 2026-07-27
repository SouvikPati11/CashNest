import 'package:flutter/foundation.dart';

/// A CMS content page such as `about`, `privacy-policy`, or `terms`
/// (`GET /v1/cms/{slug}`).
@immutable
class CmsPage {
  const CmsPage({
    required this.slug,
    required this.title,
    required this.body,
    this.locale = 'en',
    this.version,
    this.effectiveAt,
  });

  final String slug;
  final String title;
  final String body;
  final String locale;
  final int? version;
  final DateTime? effectiveAt;

  static DateTime? _date(dynamic v) => v is String ? DateTime.tryParse(v) : null;

  factory CmsPage.fromJson(Map<String, dynamic> json) {
    return CmsPage(
      slug: json['slug'] as String? ?? '',
      title: json['title'] as String? ?? '',
      body: json['body'] as String? ?? '',
      locale: json['locale'] as String? ?? 'en',
      version: (json['version'] as num?)?.toInt(),
      effectiveAt: _date(json['effective_at']),
    );
  }
}

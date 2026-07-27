import 'package:flutter/foundation.dart';

/// An offerwall provider (display only; secrets never returned).
@immutable
class OfferProvider {
  const OfferProvider({required this.slug, required this.name, this.logoUrl});

  final String slug;
  final String name;
  final String? logoUrl;

  factory OfferProvider.fromJson(Map<String, dynamic> json) {
    return OfferProvider(
      slug: json['slug'] as String? ?? '',
      name: json['name'] as String? ?? '',
      logoUrl: json['logo_url'] as String?,
    );
  }
}

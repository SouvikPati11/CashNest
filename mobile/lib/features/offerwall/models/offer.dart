import 'package:flutter/foundation.dart';

/// The catalog an offer belongs to.
enum OfferSource { offerwall, cpa }

/// A unified offer for both the offerwall (`/offerwall/offers`) and CPA
/// (`/cpa/offers`) catalogs. CPA-only fields ([goal], [trackingUrl]) and
/// offerwall-only fields ([category]) are nullable.
@immutable
class Offer {
  const Offer({
    required this.uuid,
    required this.title,
    required this.payoutCoins,
    required this.source,
    this.description,
    this.iconUrl,
    this.category,
    this.provider,
    this.goal,
    this.trackingUrl,
  });

  final String uuid;
  final String title;
  final int payoutCoins;
  final OfferSource source;
  final String? description;
  final String? iconUrl;
  final String? category;
  final String? provider;
  final String? goal;
  final String? trackingUrl;

  static int _int(dynamic v) => (v as num?)?.toInt() ?? 0;

  factory Offer.fromJson(Map<String, dynamic> json, {OfferSource source = OfferSource.offerwall}) {
    return Offer(
      uuid: json['uuid'] as String? ?? '',
      title: json['title'] as String? ?? '',
      payoutCoins: _int(json['payout_coins']),
      source: source,
      description: json['description'] as String?,
      iconUrl: json['icon_url'] as String?,
      category: json['category'] as String?,
      provider: json['provider'] as String? ?? (json['provider_slug'] as String?),
      goal: json['goal'] as String?,
      trackingUrl: json['tracking_url'] as String?,
    );
  }
}

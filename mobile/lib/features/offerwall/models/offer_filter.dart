import 'package:flutter/foundation.dart';

/// Filters for the offer catalogs (`filter[...]` query params). `provider`/
/// `category` apply to the offerwall; `country` applies to CPA.
@immutable
class OfferFilter {
  const OfferFilter({this.provider, this.category, this.country});

  final String? provider;
  final String? category;
  final String? country;

  static const OfferFilter none = OfferFilter();

  bool get isActive => provider != null || category != null || country != null;

  int get activeCount => [
        if (provider != null) 1,
        if (category != null) 1,
        if (country != null) 1,
      ].length;

  Map<String, dynamic> toQuery() {
    return <String, dynamic>{
      if (provider != null) 'filter[provider]': provider,
      if (category != null) 'filter[category]': category,
      if (country != null) 'filter[country]': country,
    };
  }

  OfferFilter copyWith({
    String? provider,
    bool resetProvider = false,
    String? category,
    bool resetCategory = false,
    String? country,
    bool resetCountry = false,
  }) {
    return OfferFilter(
      provider: resetProvider ? null : (provider ?? this.provider),
      category: resetCategory ? null : (category ?? this.category),
      country: resetCountry ? null : (country ?? this.country),
    );
  }

  @override
  bool operator ==(Object other) =>
      other is OfferFilter &&
      other.provider == provider &&
      other.category == category &&
      other.country == country;

  @override
  int get hashCode => Object.hash(provider, category, country);
}

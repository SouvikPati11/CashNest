import 'package:cashnest/features/offerwall/models/offer_filter.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('toQuery maps only set fields with filter[...] keys', () {
    const f = OfferFilter(provider: 'adgate', category: 'games', country: 'US');
    final q = f.toQuery();
    expect(q['filter[provider]'], 'adgate');
    expect(q['filter[category]'], 'games');
    expect(q['filter[country]'], 'US');
  });

  test('none is inactive and empty', () {
    expect(OfferFilter.none.isActive, isFalse);
    expect(OfferFilter.none.toQuery(), isEmpty);
    expect(OfferFilter.none.activeCount, 0);
  });

  test('activeCount counts set fields', () {
    const f = OfferFilter(provider: 'adgate', category: 'games');
    expect(f.activeCount, 2);
    expect(f.isActive, isTrue);
  });

  test('copyWith reset clears a field', () {
    const f = OfferFilter(provider: 'adgate', category: 'games');
    expect(f.copyWith(resetCategory: true).category, isNull);
    expect(f.copyWith(resetCategory: true).provider, 'adgate');
  });

  test('equality is by value', () {
    expect(const OfferFilter(provider: 'a'), equals(const OfferFilter(provider: 'a')));
    expect(const OfferFilter(provider: 'a'), isNot(equals(const OfferFilter(provider: 'b'))));
  });
}

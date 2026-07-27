import 'package:cashnest/features/offerwall/models/click_result.dart';
import 'package:cashnest/features/offerwall/models/offer.dart';
import 'package:cashnest/features/offerwall/models/offer_provider.dart';
import 'package:cashnest/features/offerwall/models/offerwall_history_entry.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('OfferProvider parses slug/name/logo', () {
    final p = OfferProvider.fromJson(const {'slug': 'adgate', 'name': 'AdGate', 'logo_url': 'x'});
    expect(p.slug, 'adgate');
    expect(p.name, 'AdGate');
    expect(p.logoUrl, 'x');
  });

  test('Offer parses offerwall shape', () {
    final o = Offer.fromJson(const {
      'uuid': 'of_1',
      'title': 'Install app',
      'payout_coins': 500,
      'icon_url': 'i',
      'category': 'games',
      'provider': 'adgate',
    });
    expect(o.uuid, 'of_1');
    expect(o.payoutCoins, 500);
    expect(o.category, 'games');
    expect(o.source, OfferSource.offerwall);
  });

  test('Offer parses cpa shape with source override', () {
    final o = Offer.fromJson(
      const {'uuid': 'cpa_1', 'title': 'Sign up', 'payout_coins': 800, 'goal': 'Register', 'tracking_url': 't'},
      source: OfferSource.cpa,
    );
    expect(o.source, OfferSource.cpa);
    expect(o.goal, 'Register');
    expect(o.trackingUrl, 't');
  });

  test('ClickResult parses redirect + token', () {
    final c = ClickResult.fromJson(const {'redirect_url': 'https://p/x', 'click_token': 'ct_1'});
    expect(c.redirectUrl, 'https://p/x');
    expect(c.clickToken, 'ct_1');
  });

  test('OfferwallHistoryEntry parses from a wallet row', () {
    final e = OfferwallHistoryEntry.fromJson(const {
      'uuid': 'wt_1',
      'amount': 100,
      'type': 'offerwall',
      'description': 'AdGate offer',
      'created_at': '2026-07-24T18:00:00Z',
    });
    expect(e.coins, 100);
    expect(e.type, 'offerwall');
    expect(e.description, 'AdGate offer');
    expect(e.createdAt, isNotNull);
  });
}

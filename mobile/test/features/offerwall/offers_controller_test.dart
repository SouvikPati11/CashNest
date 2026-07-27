import 'package:cashnest/core/network/api_result.dart';
import 'package:cashnest/features/offerwall/application/offers_controller.dart';
import 'package:cashnest/features/offerwall/data/offerwall_repository.dart';
import 'package:cashnest/features/offerwall/models/click_result.dart';
import 'package:cashnest/features/offerwall/models/offer.dart';
import 'package:cashnest/features/offerwall/models/offer_filter.dart';
import 'package:cashnest/features/offerwall/models/offer_provider.dart';
import 'package:cashnest/features/offerwall/models/offerwall_history_entry.dart';
import 'package:cashnest/features/offerwall/models/page_result.dart';
import 'package:flutter_test/flutter_test.dart';

class _FakeRepo implements OfferwallRepository {
  OfferFilter? lastOffersFilter;
  OfferFilter? lastCpaFilter;

  @override
  Future<ApiResult<PageResult<Offer>>> fetchOffers({
    OfferFilter filter = OfferFilter.none,
    String? cursor,
    int limit = 20,
  }) async {
    lastOffersFilter = filter;
    return const ApiResult.success(PageResult(items: [
      Offer(uuid: 'o1', title: 'Install Game', payoutCoins: 100, source: OfferSource.offerwall, category: 'games'),
      Offer(uuid: 'o2', title: 'Survey', payoutCoins: 50, source: OfferSource.offerwall, category: 'surveys'),
    ], hasMore: false));
  }

  @override
  Future<ApiResult<PageResult<Offer>>> fetchCpaOffers({
    OfferFilter filter = OfferFilter.none,
    String? cursor,
    int limit = 20,
  }) async {
    lastCpaFilter = filter;
    return const ApiResult.success(PageResult(items: [
      Offer(uuid: 'c1', title: 'CPA Signup', payoutCoins: 800, source: OfferSource.cpa),
    ], hasMore: false));
  }

  @override
  Future<ApiResult<List<OfferProvider>>> fetchProviders() async => const ApiResult.success([]);

  @override
  Future<ApiResult<Offer>> fetchOffer(String uuid) async =>
      const ApiResult.success(Offer(uuid: 'o1', title: 'x', payoutCoins: 1, source: OfferSource.offerwall));

  @override
  Future<ApiResult<Offer>> fetchCpaOffer(String uuid) async =>
      const ApiResult.success(Offer(uuid: 'c1', title: 'x', payoutCoins: 1, source: OfferSource.cpa));

  @override
  Future<ApiResult<ClickResult>> clickOffer(String uuid) async =>
      const ApiResult.success(ClickResult(redirectUrl: 'https://p/x', clickToken: 'ct_1'));

  @override
  Future<ApiResult<PageResult<OfferwallHistoryEntry>>> fetchHistory({String? cursor, int limit = 20}) async =>
      const ApiResult.success(PageResult(items: [], hasMore: false));
}

void main() {
  late _FakeRepo repo;

  setUp(() => repo = _FakeRepo());

  test('offerwall source loads offers', () async {
    final c = OffersController(repo);
    await c.load();
    expect(c.debugState.items, hasLength(2));
    c.dispose();
  });

  test('cpa source uses the cpa endpoint', () async {
    final c = OffersController(repo, source: OfferSource.cpa);
    await c.load();
    expect(c.debugState.items.single.uuid, 'c1');
    expect(repo.lastCpaFilter, isNotNull);
    c.dispose();
  });

  test('applyFilter reloads with the new filter', () async {
    final c = OffersController(repo);
    await c.load();
    await c.applyFilter(const OfferFilter(category: 'games'));
    expect(c.filter.category, 'games');
    expect(repo.lastOffersFilter?.category, 'games');
    c.dispose();
  });

  test('search matches title and category client-side', () async {
    final c = OffersController(repo);
    await c.load();
    c.setSearch('survey');
    expect(c.visibleItems.single.uuid, 'o2');
    c.setSearch('game');
    expect(c.visibleItems.single.uuid, 'o1');
    c.dispose();
  });
}

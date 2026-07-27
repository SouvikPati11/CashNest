import '../../../core/network/api_result.dart';
import '../models/click_result.dart';
import '../models/offer.dart';
import '../models/offer_filter.dart';
import '../models/offer_provider.dart';
import '../models/offerwall_history_entry.dart';
import '../models/page_result.dart';

/// Contract for offerwall + CPA catalogs, clicks, and earning history.
abstract interface class OfferwallRepository {
  Future<ApiResult<List<OfferProvider>>> fetchProviders();

  Future<ApiResult<PageResult<Offer>>> fetchOffers({
    OfferFilter filter = OfferFilter.none,
    String? cursor,
    int limit = 20,
  });

  Future<ApiResult<Offer>> fetchOffer(String uuid);

  Future<ApiResult<ClickResult>> clickOffer(String uuid);

  Future<ApiResult<PageResult<Offer>>> fetchCpaOffers({
    OfferFilter filter = OfferFilter.none,
    String? cursor,
    int limit = 20,
  });

  Future<ApiResult<Offer>> fetchCpaOffer(String uuid);

  Future<ApiResult<PageResult<OfferwallHistoryEntry>>> fetchHistory({
    String? cursor,
    int limit = 20,
  });
}

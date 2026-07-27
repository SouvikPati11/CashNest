import '../../../core/network/api_result.dart';
import '../data/offerwall_repository.dart';
import '../models/offer.dart';
import '../models/offer_filter.dart';
import '../models/page_result.dart';
import 'paginated_list_controller.dart';

/// Paginated offer catalog for either the offerwall or CPA source, with
/// server-side filters and client-side title/category search.
class OffersController extends PaginatedListController<Offer> {
  OffersController(this._repository, {this.source = OfferSource.offerwall});

  final OfferwallRepository _repository;
  final OfferSource source;

  OfferFilter _filter = OfferFilter.none;
  OfferFilter get filter => _filter;

  @override
  Future<ApiResult<PageResult<Offer>>> fetchPage({String? cursor}) {
    return source == OfferSource.cpa
        ? _repository.fetchCpaOffers(filter: _filter, cursor: cursor, limit: pageSize)
        : _repository.fetchOffers(filter: _filter, cursor: cursor, limit: pageSize);
  }

  @override
  bool matchesSearch(Offer item, String query) {
    return item.title.toLowerCase().contains(query) ||
        (item.category?.toLowerCase().contains(query) ?? false) ||
        (item.provider?.toLowerCase().contains(query) ?? false);
  }

  Future<void> applyFilter(OfferFilter filter) async {
    _filter = filter;
    await load();
  }

  Future<void> clearFilter() => applyFilter(OfferFilter.none);
}

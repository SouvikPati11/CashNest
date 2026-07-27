import '../../../core/network/api_result.dart';
import '../../offerwall/application/paginated_list_controller.dart';
import '../../offerwall/models/page_result.dart';
import '../data/referral_repository.dart';
import '../models/referral_earning.dart';

/// Paginated referral commission history.
class EarningsController extends PaginatedListController<ReferralEarning> {
  EarningsController(this._repository);

  final ReferralRepository _repository;

  @override
  Future<ApiResult<PageResult<ReferralEarning>>> fetchPage({String? cursor}) {
    return _repository.fetchEarnings(cursor: cursor, limit: pageSize);
  }

  @override
  bool matchesSearch(ReferralEarning item, String query) {
    return (item.source?.toLowerCase().contains(query)) ?? false;
  }
}

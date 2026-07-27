import '../../../core/network/api_result.dart';
import '../../offerwall/application/paginated_list_controller.dart';
import '../../offerwall/models/page_result.dart';
import '../data/referral_repository.dart';
import '../models/referral_entry.dart';

/// Paginated list of referred users, with an optional status filter and
/// client-side name search.
class ReferralListController extends PaginatedListController<ReferralEntry> {
  ReferralListController(this._repository);

  final ReferralRepository _repository;

  String? _status;
  String? get status => _status;

  @override
  Future<ApiResult<PageResult<ReferralEntry>>> fetchPage({String? cursor}) {
    return _repository.fetchReferrals(status: _status, cursor: cursor, limit: pageSize);
  }

  @override
  bool matchesSearch(ReferralEntry item, String query) {
    return item.refereeName.toLowerCase().contains(query);
  }

  Future<void> applyStatus(String? status) async {
    _status = status;
    await load();
  }
}

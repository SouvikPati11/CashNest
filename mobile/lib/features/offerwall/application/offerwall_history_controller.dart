import '../../../core/network/api_result.dart';
import '../data/offerwall_repository.dart';
import '../models/offerwall_history_entry.dart';
import '../models/page_result.dart';
import 'paginated_list_controller.dart';

/// Paginated offerwall/CPA earning history (from the wallet ledger).
class OfferwallHistoryController extends PaginatedListController<OfferwallHistoryEntry> {
  OfferwallHistoryController(this._repository);

  final OfferwallRepository _repository;

  @override
  Future<ApiResult<PageResult<OfferwallHistoryEntry>>> fetchPage({String? cursor}) {
    return _repository.fetchHistory(cursor: cursor, limit: pageSize);
  }

  @override
  bool matchesSearch(OfferwallHistoryEntry item, String query) {
    return (item.description?.toLowerCase().contains(query) ?? false) ||
        item.type.toLowerCase().contains(query);
  }
}

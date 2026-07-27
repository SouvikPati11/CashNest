import '../../../core/network/api_result.dart';
import '../../offerwall/application/paginated_list_controller.dart';
import '../../offerwall/models/page_result.dart';
import '../data/withdraw_repository.dart';
import '../models/withdraw_request.dart';

/// Paginated withdrawal history with an optional status filter. Reuses the
/// shared paginated-list infrastructure from the offerwall feature.
class WithdrawHistoryController extends PaginatedListController<WithdrawRequest> {
  WithdrawHistoryController(this._repository);

  final WithdrawRepository _repository;

  String? _status;
  String? get status => _status;

  @override
  Future<ApiResult<PageResult<WithdrawRequest>>> fetchPage({String? cursor}) {
    return _repository.fetchHistory(status: _status, cursor: cursor, limit: pageSize);
  }

  Future<void> applyStatus(String? status) async {
    _status = status;
    await load();
  }
}

import '../../../core/network/api_result.dart';
import '../../offerwall/application/paginated_list_controller.dart';
import '../../offerwall/models/page_result.dart';
import '../data/settings_repository.dart';
import '../models/support_ticket.dart';

/// Paginated support tickets with an optional status filter. Reuses the shared
/// paginated-list infrastructure from the offerwall feature.
class SupportTicketsController extends PaginatedListController<SupportTicket> {
  SupportTicketsController(this._repository);

  final SettingsRepository _repository;

  String? _status;
  String? get status => _status;

  @override
  Future<ApiResult<PageResult<SupportTicket>>> fetchPage({String? cursor}) {
    return _repository.fetchTickets(status: _status, cursor: cursor, limit: pageSize);
  }

  @override
  bool matchesSearch(SupportTicket item, String query) {
    return item.subject.toLowerCase().contains(query);
  }

  Future<void> applyStatus(String? status) async {
    _status = status;
    await load();
  }

  /// Prepend a newly created ticket so it shows immediately.
  void prepend(SupportTicket ticket) {
    state = state.copyWith(items: [ticket, ...state.items]);
  }
}

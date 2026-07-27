import '../../../core/network/api_result.dart';
import '../../offerwall/application/paginated_list_controller.dart';
import '../../offerwall/models/page_result.dart';
import '../data/referral_repository.dart';
import '../models/leaderboard_entry.dart';

/// Paginated leaderboard for a selectable period, with client-side name search.
class LeaderboardController extends PaginatedListController<LeaderboardEntry> {
  LeaderboardController(this._repository);

  final ReferralRepository _repository;

  String _period = 'weekly';
  String get period => _period;

  @override
  Future<ApiResult<PageResult<LeaderboardEntry>>> fetchPage({String? cursor}) {
    return _repository.fetchLeaderboard(period: _period, cursor: cursor, limit: pageSize);
  }

  @override
  bool matchesSearch(LeaderboardEntry item, String query) {
    return item.name.toLowerCase().contains(query);
  }

  Future<void> setPeriod(String period) async {
    if (_period == period) {
      return;
    }
    _period = period;
    await load();
  }
}

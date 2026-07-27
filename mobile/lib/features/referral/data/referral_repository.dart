import '../../../core/network/api_result.dart';
import '../../offerwall/models/page_result.dart';
import '../models/leaderboard_entry.dart';
import '../models/referral_earning.dart';
import '../models/referral_entry.dart';
import '../models/referral_info.dart';

/// Contract for referral info/list/earnings, code application, and the
/// leaderboard. Paginated lists reuse the shared [PageResult].
abstract interface class ReferralRepository {
  Future<ApiResult<ReferralInfo>> fetchInfo();

  Future<ApiResult<PageResult<ReferralEntry>>> fetchReferrals({
    String? status,
    String? cursor,
    int limit = 20,
  });

  Future<ApiResult<PageResult<ReferralEarning>>> fetchEarnings({
    String? cursor,
    int limit = 20,
  });

  Future<ApiResult<bool>> applyCode(String code);

  Future<ApiResult<PageResult<LeaderboardEntry>>> fetchLeaderboard({
    String period = 'weekly',
    String? cursor,
    int limit = 20,
  });

  Future<ApiResult<LeaderboardMe>> fetchMyRank({String period = 'weekly'});
}

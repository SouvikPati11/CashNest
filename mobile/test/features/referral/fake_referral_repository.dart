import 'package:cashnest/core/network/api_result.dart';
import 'package:cashnest/features/offerwall/models/page_result.dart';
import 'package:cashnest/features/referral/data/referral_repository.dart';
import 'package:cashnest/features/referral/models/leaderboard_entry.dart';
import 'package:cashnest/features/referral/models/referral_earning.dart';
import 'package:cashnest/features/referral/models/referral_entry.dart';
import 'package:cashnest/features/referral/models/referral_info.dart';

/// A configurable fake [ReferralRepository] for controller tests.
class FakeReferralRepository implements ReferralRepository {
  ApiResult<ReferralInfo> info = const ApiResult.success(ReferralInfo(
    referralCode: 'ASHA12',
    referralLink: 'https://cashnest.app/r/ASHA12',
    totalReferrals: 8,
    qualified: 5,
    totalEarnedCoins: 1200,
  ));
  ApiResult<bool> applyResult = const ApiResult.success(true);

  String? lastStatus;
  String? lastPeriod;
  int infoCalls = 0;

  @override
  Future<ApiResult<ReferralInfo>> fetchInfo() async {
    infoCalls++;
    return info;
  }

  @override
  Future<ApiResult<PageResult<ReferralEntry>>> fetchReferrals({
    String? status,
    String? cursor,
    int limit = 20,
  }) async {
    lastStatus = status;
    return const ApiResult.success(PageResult(items: [
      ReferralEntry(refereeName: 'Ravi', status: 'qualified'),
      ReferralEntry(refereeName: 'Meena', status: 'pending'),
    ], hasMore: false));
  }

  @override
  Future<ApiResult<PageResult<ReferralEarning>>> fetchEarnings({String? cursor, int limit = 20}) async {
    return const ApiResult.success(PageResult(items: [
      ReferralEarning(commissionCoins: 120, source: 'offerwall'),
    ], hasMore: false));
  }

  @override
  Future<ApiResult<bool>> applyCode(String code) async => applyResult;

  @override
  Future<ApiResult<PageResult<LeaderboardEntry>>> fetchLeaderboard({
    String period = 'weekly',
    String? cursor,
    int limit = 20,
  }) async {
    lastPeriod = period;
    return const ApiResult.success(PageResult(items: [
      LeaderboardEntry(rank: 1, name: 'Asha', score: 12000),
      LeaderboardEntry(rank: 2, name: 'Ravi', score: 9000),
    ], hasMore: false));
  }

  @override
  Future<ApiResult<LeaderboardMe>> fetchMyRank({String period = 'weekly'}) async =>
      const ApiResult.success(LeaderboardMe(rank: 42, score: 3400, period: 'weekly'));
}

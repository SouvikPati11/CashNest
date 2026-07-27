import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../app/di/providers.dart';
import '../../offerwall/application/paginated_list_state.dart';
import '../application/earnings_controller.dart';
import '../application/leaderboard_controller.dart';
import '../application/referral_info_controller.dart';
import '../application/referral_list_controller.dart';
import '../data/referral_repository.dart';
import '../data/referral_repository_impl.dart';
import '../models/leaderboard_entry.dart';
import '../models/referral_earning.dart';
import '../models/referral_entry.dart';
import '../models/referral_info.dart';

/// Riverpod wiring for the referral + leaderboard feature. Reuses the foundation
/// API client; no completed module is modified.

final referralRepositoryProvider = Provider<ReferralRepository>(
  (ref) => ReferralRepositoryImpl(ref.watch(apiClientProvider)),
);

final referralInfoControllerProvider =
    StateNotifierProvider<ReferralInfoController, AsyncValue<ReferralInfo>>(
  (ref) => ReferralInfoController(ref.watch(referralRepositoryProvider)),
);

final referralListControllerProvider =
    StateNotifierProvider<ReferralListController, PaginatedListState<ReferralEntry>>(
  (ref) => ReferralListController(ref.watch(referralRepositoryProvider)),
);

final earningsControllerProvider =
    StateNotifierProvider<EarningsController, PaginatedListState<ReferralEarning>>(
  (ref) => EarningsController(ref.watch(referralRepositoryProvider)),
);

final leaderboardControllerProvider =
    StateNotifierProvider<LeaderboardController, PaginatedListState<LeaderboardEntry>>(
  (ref) => LeaderboardController(ref.watch(referralRepositoryProvider)),
);

/// The caller's own rank for the current leaderboard period.
final myRankProvider = FutureProvider.family<LeaderboardMe, String>((ref, period) async {
  final result = await ref.watch(referralRepositoryProvider).fetchMyRank(period: period);
  return result.dataOrNull ?? const LeaderboardMe(rank: 0, score: 0);
});

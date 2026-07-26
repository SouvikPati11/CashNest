import '../../../core/network/api_result.dart';
import '../models/home_announcement.dart';
import '../models/home_banner.dart';
import '../models/home_section.dart';
import '../models/home_transaction.dart';
import '../models/wallet_balance.dart';

/// Contract for the home dashboard's aggregate reads. Returns [ApiResult] so the
/// controller can distinguish required vs best-effort failures.
abstract interface class HomeRepository {
  Future<ApiResult<List<HomeSection>>> fetchLayout();

  Future<ApiResult<WalletBalance>> fetchBalance();

  Future<ApiResult<List<HomeBanner>>> fetchBanners({String placement = 'home_top'});

  Future<ApiResult<List<HomeAnnouncement>>> fetchAnnouncements();

  Future<ApiResult<List<HomeTransaction>>> fetchRecentTransactions({int limit = 5});

  Future<ApiResult<bool>> markAnnouncementSeen(int id);
}

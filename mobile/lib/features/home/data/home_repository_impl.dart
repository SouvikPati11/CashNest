import '../../../core/network/api_client.dart';
import '../../../core/network/api_result.dart';
import '../models/home_announcement.dart';
import '../models/home_banner.dart';
import '../models/home_section.dart';
import '../models/home_transaction.dart';
import '../models/wallet_balance.dart';
import 'home_repository.dart';

/// [HomeRepository] over the foundation [ApiClient]. All endpoints are
/// JWT-authenticated (the JWT interceptor attaches the token automatically).
class HomeRepositoryImpl implements HomeRepository {
  HomeRepositoryImpl(this._client);

  final ApiClient _client;

  static List<T> _list<T>(dynamic data, T Function(Map<String, dynamic>) fromJson) {
    if (data is! List) {
      return <T>[];
    }
    return data
        .whereType<Map<String, dynamic>>()
        .map(fromJson)
        .toList(growable: false);
  }

  static Map<String, dynamic> _map(dynamic data) =>
      data is Map<String, dynamic> ? data : const <String, dynamic>{};

  @override
  Future<ApiResult<List<HomeSection>>> fetchLayout() {
    return _client.get<List<HomeSection>>(
      '/home/layout',
      decoder: (data) {
        final sections = _list(data, HomeSection.fromJson)
          ..sort((a, b) => a.sortOrder.compareTo(b.sortOrder));
        return sections;
      },
    );
  }

  @override
  Future<ApiResult<WalletBalance>> fetchBalance() {
    return _client.get<WalletBalance>(
      '/wallet',
      decoder: (data) => WalletBalance.fromJson(_map(data)),
    );
  }

  @override
  Future<ApiResult<List<HomeBanner>>> fetchBanners({String placement = 'home_top'}) {
    return _client.get<List<HomeBanner>>(
      '/banners',
      query: {'filter[placement]': placement},
      decoder: (data) {
        final banners = _list(data, HomeBanner.fromJson)
          ..sort((a, b) => a.sortOrder.compareTo(b.sortOrder));
        return banners;
      },
    );
  }

  @override
  Future<ApiResult<List<HomeAnnouncement>>> fetchAnnouncements() {
    return _client.get<List<HomeAnnouncement>>(
      '/announcements',
      decoder: (data) => _list(data, HomeAnnouncement.fromJson),
    );
  }

  @override
  Future<ApiResult<List<HomeTransaction>>> fetchRecentTransactions({int limit = 5}) {
    return _client.get<List<HomeTransaction>>(
      '/wallet/transactions',
      query: {'limit': limit},
      decoder: (data) => _list(data, HomeTransaction.fromJson),
    );
  }

  @override
  Future<ApiResult<bool>> markAnnouncementSeen(int id) {
    return _client.post<bool>(
      '/announcements/$id/seen',
      decoder: (data) => _map(data)['seen'] == true,
    );
  }
}

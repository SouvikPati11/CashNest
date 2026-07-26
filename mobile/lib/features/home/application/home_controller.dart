import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_result.dart';
import '../../auth/models/auth_user.dart';
import '../data/home_repository.dart';
import '../models/home_data.dart';

/// Loads and refreshes the aggregated home dashboard data.
///
/// `layout` and `balance` are required (a failure surfaces as an error state for
/// retry); banners, announcements, and recent transactions are best-effort and
/// degrade to empty so a partial outage still renders a useful dashboard.
class HomeController extends StateNotifier<AsyncValue<HomeData>> {
  HomeController(this._repository, this._currentUser)
      : super(const AsyncValue.loading());

  final HomeRepository _repository;
  final AuthUser? Function() _currentUser;

  Future<void> load() async {
    state = const AsyncValue.loading();
    state = await AsyncValue.guard(_fetch);
  }

  /// Re-fetch without blanking the screen: the current dashboard stays visible
  /// (e.g. under the pull-to-refresh spinner) until the new data resolves.
  Future<void> refresh() async {
    state = await AsyncValue.guard(_fetch);
  }

  Future<HomeData> _fetch() async {
    final sections = _require(await _repository.fetchLayout());
    final balance = _require(await _repository.fetchBalance());

    final banners = (await _repository.fetchBanners()).dataOrNull ?? const [];
    final announcements = (await _repository.fetchAnnouncements()).dataOrNull ?? const [];
    final recent = (await _repository.fetchRecentTransactions()).dataOrNull ?? const [];

    return HomeData(
      balance: balance,
      sections: sections,
      user: _currentUser(),
      banners: banners,
      announcements: announcements,
      recentTransactions: recent,
    );
  }

  /// Dismiss an announcement: persist the seen state and remove it locally.
  Future<void> dismissAnnouncement(int id) async {
    await _repository.markAnnouncementSeen(id);
    final current = state.valueOrNull;
    if (current != null) {
      final remaining = current.announcements.where((a) => a.id != id).toList();
      state = AsyncValue.data(current.copyWith(announcements: remaining));
    }
  }

  T _require<T>(ApiResult<T> result) {
    return switch (result) {
      ApiSuccess<T>(:final data) => data,
      ApiFailure<T>(:final error) => throw error,
    };
  }
}

import 'package:cashnest/core/error/app_exception.dart';
import 'package:cashnest/core/network/api_result.dart';
import 'package:cashnest/features/auth/models/auth_user.dart';
import 'package:cashnest/features/home/application/home_controller.dart';
import 'package:cashnest/features/home/data/home_repository.dart';
import 'package:cashnest/features/home/models/home_announcement.dart';
import 'package:cashnest/features/home/models/home_banner.dart';
import 'package:cashnest/features/home/models/home_section.dart';
import 'package:cashnest/features/home/models/home_transaction.dart';
import 'package:cashnest/features/home/models/wallet_balance.dart';
import 'package:flutter_test/flutter_test.dart';

const _balance = WalletBalance(
  coinBalance: 100,
  coinReserved: 0,
  available: 100,
  cashBalance: '1.0000',
  currency: 'INR',
  lifetimeEarned: 100,
  lifetimeSpent: 0,
);

const _user = AuthUser(uuid: 'u1', name: 'Asha', email: 'asha@x.io');

class _FakeHomeRepository implements HomeRepository {
  ApiResult<List<HomeSection>> layout = const ApiResult.success([
    HomeSection(type: 'quick_actions', sortOrder: 0),
  ]);
  ApiResult<WalletBalance> balance = const ApiResult.success(_balance);
  ApiResult<List<HomeBanner>> banners = const ApiResult.success([
    HomeBanner(id: 1, imageUrl: 'https://cdn/a.png'),
  ]);
  ApiResult<List<HomeAnnouncement>> announcements = const ApiResult.success([
    HomeAnnouncement(id: 9, title: 'Hi', body: 'Welcome'),
  ]);
  ApiResult<List<HomeTransaction>> recent = const ApiResult.success([
    HomeTransaction(uuid: 't1', direction: 'credit', amount: 10, type: 'bonus'),
  ]);
  int seenId = -1;

  @override
  Future<ApiResult<List<HomeSection>>> fetchLayout() async => layout;

  @override
  Future<ApiResult<WalletBalance>> fetchBalance() async => balance;

  @override
  Future<ApiResult<List<HomeBanner>>> fetchBanners({String placement = 'home_top'}) async => banners;

  @override
  Future<ApiResult<List<HomeAnnouncement>>> fetchAnnouncements() async => announcements;

  @override
  Future<ApiResult<List<HomeTransaction>>> fetchRecentTransactions({int limit = 5}) async => recent;

  @override
  Future<ApiResult<bool>> markAnnouncementSeen(int id) async {
    seenId = id;
    return const ApiResult.success(true);
  }
}

void main() {
  late _FakeHomeRepository repo;

  HomeController build() => HomeController(repo, () => _user);

  setUp(() => repo = _FakeHomeRepository());

  test('starts in loading state', () {
    final controller = build();
    expect(controller.debugState, isA<AsyncLoading<dynamic>>());
    controller.dispose();
  });

  test('load aggregates all sources on success', () async {
    final controller = build();
    await controller.load();

    final data = controller.debugState.valueOrNull;
    expect(data, isNotNull);
    expect(data!.sections, hasLength(1));
    expect(data.balance.coinBalance, 100);
    expect(data.user, _user);
    expect(data.banners, hasLength(1));
    expect(data.announcements, hasLength(1));
    expect(data.recentTransactions, hasLength(1));
    controller.dispose();
  });

  test('required layout failure surfaces an error state', () async {
    repo.layout = const ApiResult.failure(ServerException('boom', statusCode: 500));
    final controller = build();
    await controller.load();

    expect(controller.debugState, isA<AsyncError<dynamic>>());
    controller.dispose();
  });

  test('required balance failure surfaces an error state', () async {
    repo.balance = const ApiResult.failure(NetworkException('offline'));
    final controller = build();
    await controller.load();

    expect(controller.debugState, isA<AsyncError<dynamic>>());
    controller.dispose();
  });

  test('ancillary failures degrade to empty without erroring', () async {
    repo.banners = const ApiResult.failure(ServerException('no banners', statusCode: 500));
    repo.announcements = const ApiResult.failure(ServerException('no ann', statusCode: 500));
    repo.recent = const ApiResult.failure(ServerException('no tx', statusCode: 500));
    final controller = build();
    await controller.load();

    final data = controller.debugState.valueOrNull;
    expect(data, isNotNull);
    expect(data!.banners, isEmpty);
    expect(data.announcements, isEmpty);
    expect(data.recentTransactions, isEmpty);
    controller.dispose();
  });

  test('dismissAnnouncement persists seen and removes it locally', () async {
    final controller = build();
    await controller.load();

    await controller.dismissAnnouncement(9);

    expect(repo.seenId, 9);
    expect(controller.debugState.valueOrNull!.announcements, isEmpty);
    controller.dispose();
  });
}

import 'package:cashnest/features/offerwall/application/paginated_list_state.dart';
import 'package:cashnest/features/referral/application/earnings_controller.dart';
import 'package:cashnest/features/referral/application/referral_list_controller.dart';
import 'package:flutter_test/flutter_test.dart';

import 'fake_referral_repository.dart';

void main() {
  late FakeReferralRepository repo;

  setUp(() => repo = FakeReferralRepository());

  test('referral list loads referred users', () async {
    final c = ReferralListController(repo);
    await c.load();
    expect(c.debugState.status, ListStatus.ready);
    expect(c.debugState.items, hasLength(2));
    c.dispose();
  });

  test('applyStatus reloads with the status filter', () async {
    final c = ReferralListController(repo);
    await c.load();
    await c.applyStatus('qualified');
    expect(c.status, 'qualified');
    expect(repo.lastStatus, 'qualified');
    c.dispose();
  });

  test('search filters referred users by name', () async {
    final c = ReferralListController(repo);
    await c.load();
    c.setSearch('meena');
    expect(c.visibleItems.single.refereeName, 'Meena');
    c.dispose();
  });

  test('earnings controller loads commission history', () async {
    final c = EarningsController(repo);
    await c.load();
    expect(c.debugState.items.single.commissionCoins, 120);
    c.dispose();
  });
}

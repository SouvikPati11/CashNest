import 'package:cashnest/features/referral/application/leaderboard_controller.dart';
import 'package:flutter_test/flutter_test.dart';

import 'fake_referral_repository.dart';

void main() {
  late FakeReferralRepository repo;

  setUp(() => repo = FakeReferralRepository());

  test('loads the weekly leaderboard by default', () async {
    final c = LeaderboardController(repo);
    await c.load();
    expect(c.debugState.items, hasLength(2));
    expect(repo.lastPeriod, 'weekly');
    c.dispose();
  });

  test('setPeriod reloads with the new period', () async {
    final c = LeaderboardController(repo);
    await c.load();
    await c.setPeriod('monthly');
    expect(c.period, 'monthly');
    expect(repo.lastPeriod, 'monthly');
    c.dispose();
  });

  test('setPeriod is a no-op when unchanged', () async {
    final c = LeaderboardController(repo);
    await c.load();
    await c.setPeriod('weekly');
    expect(c.period, 'weekly');
    c.dispose();
  });

  test('search filters by name', () async {
    final c = LeaderboardController(repo);
    await c.load();
    c.setSearch('ravi');
    expect(c.visibleItems.single.name, 'Ravi');
    c.dispose();
  });
}

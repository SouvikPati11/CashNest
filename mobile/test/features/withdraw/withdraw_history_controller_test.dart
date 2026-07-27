import 'package:cashnest/features/offerwall/application/paginated_list_state.dart';
import 'package:cashnest/features/withdraw/application/withdraw_history_controller.dart';
import 'package:flutter_test/flutter_test.dart';

import 'fake_withdraw_repository.dart';

void main() {
  late FakeWithdrawRepository repo;

  setUp(() => repo = FakeWithdrawRepository());

  test('load populates the history list', () async {
    final c = WithdrawHistoryController(repo);
    await c.load();
    expect(c.debugState.status, ListStatus.ready);
    expect(c.debugState.items, hasLength(2));
    c.dispose();
  });

  test('applyStatus reloads with the status filter', () async {
    final c = WithdrawHistoryController(repo);
    await c.load();
    await c.applyStatus('paid');
    expect(c.status, 'paid');
    expect(repo.lastStatus, 'paid');
    c.dispose();
  });
}

import 'package:cashnest/core/error/app_exception.dart';
import 'package:cashnest/core/network/api_result.dart';
import 'package:cashnest/features/withdraw/application/withdraw_form_controller.dart';
import 'package:cashnest/features/withdraw/application/withdraw_form_state.dart';
import 'package:flutter_test/flutter_test.dart';

import 'fake_withdraw_repository.dart';

void main() {
  late FakeWithdrawRepository repo;

  setUp(() => repo = FakeWithdrawRepository());

  test('load fetches methods + conversion and selects the first method', () async {
    final c = WithdrawFormController(repo);
    await c.load();
    expect(c.debugState.loadStatus, WithdrawLoad.ready);
    expect(c.debugState.methods, hasLength(1));
    expect(c.debugState.method?.code, 'upi');
    expect(c.debugState.conversion.rate, closeTo(0.001, 1e-9));
    c.dispose();
  });

  test('methods failure surfaces the error load state', () async {
    repo.methods = const ApiResult.failure(ServerException('boom', statusCode: 500));
    final c = WithdrawFormController(repo);
    await c.load();
    expect(c.debugState.loadStatus, WithdrawLoad.error);
    c.dispose();
  });

  test('live quote reflects coins, rate, and fee', () async {
    final c = WithdrawFormController(repo);
    await c.load();
    c.setCoins(5000);
    final quote = c.debugState.quote;
    expect(quote.cash, closeTo(5.0, 1e-9));
    expect(quote.fee, closeTo(0.10, 1e-9));
    expect(quote.net, closeTo(4.90, 1e-9));
    c.dispose();
  });

  test('canSubmit requires valid amount and all details', () async {
    final c = WithdrawFormController(repo);
    await c.load();
    c.setCoins(4000); // below min
    expect(c.debugState.canSubmit, isFalse);
    c.setCoins(5000);
    expect(c.debugState.canSubmit, isFalse); // detail missing
    c.setDetail('upi_id', 'ravi@upi');
    expect(c.debugState.canSubmit, isTrue);
    c.dispose();
  });

  test('submit sends the request and returns it', () async {
    final c = WithdrawFormController(repo);
    await c.load();
    c.setCoins(5000);
    c.setDetail('upi_id', 'ravi@upi');
    final request = await c.submit();
    expect(request.uuid, 'wr_1');
    expect(repo.lastCoins, 5000);
    expect(repo.lastMethodCode, 'upi');
    expect(repo.lastDetail, {'upi_id': 'ravi@upi'});
    c.dispose();
  });

  test('submit throws and records error on failure', () async {
    repo.createResult = const ApiResult.failure(ValidationException('INSUFFICIENT_BALANCE'));
    final c = WithdrawFormController(repo);
    await c.load();
    c.setCoins(5000);
    c.setDetail('upi_id', 'ravi@upi');
    await expectLater(c.submit(), throwsA(isA<AppException>()));
    expect(c.debugState.submitError, isNotNull);
    expect(c.debugState.submitting, isFalse);
    c.dispose();
  });
}

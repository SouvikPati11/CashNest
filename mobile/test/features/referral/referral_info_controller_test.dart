import 'package:cashnest/core/error/app_exception.dart';
import 'package:cashnest/core/network/api_result.dart';
import 'package:cashnest/features/referral/application/referral_info_controller.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

import 'fake_referral_repository.dart';

void main() {
  late FakeReferralRepository repo;

  setUp(() => repo = FakeReferralRepository());

  test('load returns referral info', () async {
    final c = ReferralInfoController(repo);
    await c.load();
    expect(c.debugState.valueOrNull?.referralCode, 'ASHA12');
    c.dispose();
  });

  test('info failure surfaces error state', () async {
    repo.info = const ApiResult.failure(ServerException('boom', statusCode: 500));
    final c = ReferralInfoController(repo);
    await c.load();
    expect(c.debugState, isA<AsyncError<dynamic>>());
    c.dispose();
  });

  test('applyCode applied refreshes info', () async {
    final c = ReferralInfoController(repo);
    await c.load();
    final callsBefore = repo.infoCalls;
    final applied = await c.applyCode('FRIEND1');
    expect(applied, isTrue);
    expect(repo.infoCalls, greaterThan(callsBefore));
    c.dispose();
  });

  test('applyCode throws on failure', () async {
    repo.applyResult = const ApiResult.failure(ValidationException('bad code'));
    final c = ReferralInfoController(repo);
    await c.load();
    await expectLater(c.applyCode('BAD'), throwsA(isA<AppException>()));
    c.dispose();
  });
}

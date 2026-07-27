import 'package:cashnest/core/error/app_exception.dart';
import 'package:cashnest/core/network/api_result.dart';
import 'package:cashnest/features/rewards/application/checkin_controller.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

import 'fake_rewards_repository.dart';

void main() {
  late FakeRewardsRepository repo;

  setUp(() => repo = FakeRewardsRepository());

  test('load aggregates status and calendar', () async {
    final controller = CheckinController(repo);
    await controller.load();
    final data = controller.debugState.valueOrNull;
    expect(data, isNotNull);
    expect(data!.status.canClaimToday, isTrue);
    expect(data.status.currentStreak, 3);
    controller.dispose();
  });

  test('status failure surfaces an error state', () async {
    repo.checkinStatus = const ApiResult.failure(ServerException('boom', statusCode: 500));
    final controller = CheckinController(repo);
    await controller.load();
    expect(controller.debugState, isA<AsyncError<dynamic>>());
    controller.dispose();
  });

  test('calendar failure degrades but status still loads', () async {
    repo.checkinCalendar = const ApiResult.failure(ServerException('no cal', statusCode: 500));
    final controller = CheckinController(repo);
    await controller.load();
    final data = controller.debugState.valueOrNull;
    expect(data, isNotNull);
    expect(data!.calendar.ladder, isEmpty);
    controller.dispose();
  });

  test('claim returns result and marks today claimed', () async {
    final controller = CheckinController(repo);
    await controller.load();
    final result = await controller.claim();
    expect(result.coinsAwarded, 30);
    expect(controller.debugState.valueOrNull!.status.canClaimToday, isFalse);
    expect(controller.debugState.valueOrNull!.status.currentStreak, 4);
    controller.dispose();
  });

  test('claim throws on failure', () async {
    repo.checkinClaim = const ApiResult.failure(ServerException('already', statusCode: 409));
    final controller = CheckinController(repo);
    await controller.load();
    await expectLater(controller.claim(), throwsA(isA<AppException>()));
    controller.dispose();
  });
}

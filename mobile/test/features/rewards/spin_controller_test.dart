import 'package:cashnest/core/error/app_exception.dart';
import 'package:cashnest/core/network/api_result.dart';
import 'package:cashnest/features/rewards/application/spin_controller.dart';
import 'package:cashnest/features/rewards/models/spin_result.dart';
import 'package:flutter_test/flutter_test.dart';

import 'fake_rewards_repository.dart';

void main() {
  late FakeRewardsRepository repo;

  setUp(() => repo = FakeRewardsRepository());

  test('load returns spin status with sorted segments', () async {
    final controller = SpinController(repo);
    await controller.load();
    final status = controller.debugState.valueOrNull;
    expect(status, isNotNull);
    expect(status!.spinsRemaining, 2);
    expect(status.segments.first.id, 'seg_1');
    controller.dispose();
  });

  test('spin returns result and updates remaining from server value', () async {
    final controller = SpinController(repo);
    await controller.load();
    final result = await controller.spin();
    expect(result.segmentId, 'seg_1');
    expect(result.rewardCoins, 50);
    expect(controller.debugState.valueOrNull!.spinsRemaining, 1);
    controller.dispose();
  });

  test('spin decrements locally when server omits remaining', () async {
    repo.spinResult = const ApiResult.success(
      SpinResult(segmentId: 'seg_2', rewardType: 'coins', rewardCoins: 20),
    );
    final controller = SpinController(repo);
    await controller.load();
    await controller.spin();
    expect(controller.debugState.valueOrNull!.spinsRemaining, 1);
    controller.dispose();
  });

  test('spin throws on limit reached', () async {
    repo.spinResult = const ApiResult<SpinResult>.failure(ServerException('limit', statusCode: 429));
    final controller = SpinController(repo);
    await controller.load();
    await expectLater(controller.spin(), throwsA(isA<AppException>()));
    controller.dispose();
  });
}

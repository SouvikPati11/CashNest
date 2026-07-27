import 'package:cashnest/core/error/app_exception.dart';
import 'package:cashnest/core/network/api_result.dart';
import 'package:cashnest/features/rewards/application/scratch_controller.dart';
import 'package:cashnest/features/rewards/models/scratch_card.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

import 'fake_rewards_repository.dart';

void main() {
  late FakeRewardsRepository repo;

  setUp(() => repo = FakeRewardsRepository());

  test('load returns available cards', () async {
    final controller = ScratchController(repo);
    await controller.load();
    expect(controller.debugState.valueOrNull, hasLength(2));
    controller.dispose();
  });

  test('reveal replaces the card in the list with the revealed one', () async {
    final controller = ScratchController(repo);
    await controller.load();
    final revealed = await controller.reveal('sc_1');
    expect(revealed.isRevealed, isTrue);
    expect(revealed.rewardCoins, 50);
    final card = controller.debugState.valueOrNull!.firstWhere((c) => c.uuid == 'sc_1');
    expect(card.status, 'revealed');
    controller.dispose();
  });

  test('claim removes the card and returns the result', () async {
    final controller = ScratchController(repo);
    await controller.load();
    final result = await controller.claim('sc_1');
    expect(result.coinsAwarded, 50);
    expect(controller.debugState.valueOrNull!.any((c) => c.uuid == 'sc_1'), isFalse);
    controller.dispose();
  });

  test('reveal throws on failure', () async {
    repo.revealResult = const ApiResult<ScratchCard>.failure(ServerException('gone', statusCode: 410));
    final controller = ScratchController(repo);
    await controller.load();
    await expectLater(controller.reveal('sc_1'), throwsA(isA<AppException>()));
    controller.dispose();
  });
}

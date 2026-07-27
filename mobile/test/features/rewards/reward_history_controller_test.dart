import 'package:cashnest/core/error/app_exception.dart';
import 'package:cashnest/core/network/api_result.dart';
import 'package:cashnest/features/rewards/application/reward_history_controller.dart';
import 'package:cashnest/features/rewards/models/reward_history_entry.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

import 'fake_rewards_repository.dart';

void main() {
  late FakeRewardsRepository repo;

  setUp(() => repo = FakeRewardsRepository());

  test('merges the three sources sorted by recency (newest first)', () async {
    repo.scratchHistory = ApiResult.success([
      RewardHistoryEntry(kind: RewardKind.scratch, id: 's1', coins: 50, createdAt: DateTime.utc(2026, 7, 20)),
    ]);
    repo.spinHistory = ApiResult.success([
      RewardHistoryEntry(kind: RewardKind.spin, id: 'sp1', coins: 20, createdAt: DateTime.utc(2026, 7, 25)),
    ]);
    repo.taskHistory = ApiResult.success([
      RewardHistoryEntry(kind: RewardKind.task, id: 't1', coins: 100, createdAt: DateTime.utc(2026, 7, 22)),
    ]);

    final controller = RewardHistoryController(repo);
    await controller.load();

    final entries = controller.debugState.valueOrNull;
    expect(entries, hasLength(3));
    expect(entries!.map((e) => e.kind), [RewardKind.spin, RewardKind.task, RewardKind.scratch]);
    controller.dispose();
  });

  test('a failing source is skipped, others still merge', () async {
    repo.scratchHistory = const ApiResult.failure(ServerException('boom', statusCode: 500));
    repo.spinHistory = ApiResult.success([
      RewardHistoryEntry(kind: RewardKind.spin, id: 'sp1', coins: 20, createdAt: DateTime.utc(2026, 7, 25)),
    ]);
    repo.taskHistory = const ApiResult.success([]);

    final controller = RewardHistoryController(repo);
    await controller.load();

    final entries = controller.debugState.valueOrNull;
    expect(entries, hasLength(1));
    expect(entries!.single.kind, RewardKind.spin);
    controller.dispose();
  });
}

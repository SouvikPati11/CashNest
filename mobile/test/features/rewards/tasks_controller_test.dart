import 'package:cashnest/core/network/api_result.dart';
import 'package:cashnest/features/rewards/application/tasks_controller.dart';
import 'package:cashnest/features/rewards/models/task_action_result.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

import 'fake_rewards_repository.dart';

void main() {
  late FakeRewardsRepository repo;

  setUp(() => repo = FakeRewardsRepository());

  test('load returns tasks', () async {
    final controller = TasksController(repo);
    await controller.load();
    expect(controller.debugState.valueOrNull, hasLength(1));
    controller.dispose();
  });

  test('complete credited bumps the completion count', () async {
    final controller = TasksController(repo);
    await controller.load();
    final result = await controller.complete('t_1');
    expect(result.isCredited, isTrue);
    expect(controller.debugState.valueOrNull!.single.myCompletions, 1);
    controller.dispose();
  });

  test('complete pending does not bump the count', () async {
    repo.completeResult = const ApiResult.success(TaskActionResult(status: 'pending'));
    final controller = TasksController(repo);
    await controller.load();
    final result = await controller.complete('t_1');
    expect(result.isPending, isTrue);
    expect(controller.debugState.valueOrNull!.single.myCompletions, 0);
    controller.dispose();
  });

  test('start returns the action result', () async {
    final controller = TasksController(repo);
    await controller.load();
    final result = await controller.start('t_1');
    expect(result.status, 'started');
    expect(result.completionUuid, 'tc_1');
    controller.dispose();
  });
}

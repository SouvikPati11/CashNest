import 'package:flutter/foundation.dart';

/// An earnable task (`GET /v1/tasks`, `/tasks/{uuid}`).
@immutable
class RewardTask {
  const RewardTask({
    required this.uuid,
    required this.title,
    required this.rewardCoins,
    this.description,
    this.taskType,
    this.actionUrl,
    this.iconUrl,
    this.perUserLimit = 1,
    this.myCompletions = 0,
  });

  final String uuid;
  final String title;
  final int rewardCoins;
  final String? description;
  final String? taskType;
  final String? actionUrl;
  final String? iconUrl;
  final int perUserLimit;
  final int myCompletions;

  /// Whether the user can still complete this task.
  bool get isAvailable => myCompletions < perUserLimit;

  static int _int(dynamic v, [int fallback = 0]) => (v as num?)?.toInt() ?? fallback;

  factory RewardTask.fromJson(Map<String, dynamic> json) {
    return RewardTask(
      uuid: json['uuid'] as String? ?? '',
      title: json['title'] as String? ?? '',
      rewardCoins: _int(json['reward_coins']),
      description: json['description'] as String?,
      taskType: json['task_type'] as String?,
      actionUrl: json['action_url'] as String?,
      iconUrl: json['icon_url'] as String?,
      perUserLimit: _int(json['per_user_limit'], 1),
      myCompletions: _int(json['my_completions']),
    );
  }

  RewardTask copyWith({int? myCompletions}) {
    return RewardTask(
      uuid: uuid,
      title: title,
      rewardCoins: rewardCoins,
      description: description,
      taskType: taskType,
      actionUrl: actionUrl,
      iconUrl: iconUrl,
      perUserLimit: perUserLimit,
      myCompletions: myCompletions ?? this.myCompletions,
    );
  }
}

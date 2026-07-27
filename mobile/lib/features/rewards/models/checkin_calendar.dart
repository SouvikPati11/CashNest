import 'package:flutter/foundation.dart';

/// A single rung on the check-in reward ladder.
@immutable
class CheckinLadderDay {
  const CheckinLadderDay({
    required this.day,
    required this.coins,
    this.isMilestone = false,
  });

  final int day;
  final int coins;
  final bool isMilestone;

  static int _int(dynamic v) => (v as num?)?.toInt() ?? 0;

  factory CheckinLadderDay.fromJson(Map<String, dynamic> json) {
    return CheckinLadderDay(
      day: _int(json['day']),
      coins: _int(json['coins']),
      isMilestone: json['is_milestone'] as bool? ?? false,
    );
  }
}

/// The reward ladder + current streak (`GET /v1/checkin/calendar`).
@immutable
class CheckinCalendar {
  const CheckinCalendar({required this.ladder, required this.currentStreak});

  final List<CheckinLadderDay> ladder;
  final int currentStreak;

  factory CheckinCalendar.fromJson(Map<String, dynamic> json) {
    final rawLadder = json['ladder'];
    final ladder = rawLadder is List
        ? rawLadder
            .whereType<Map<String, dynamic>>()
            .map(CheckinLadderDay.fromJson)
            .toList(growable: false)
        : const <CheckinLadderDay>[];
    return CheckinCalendar(
      ladder: ladder,
      currentStreak: (json['current_streak'] as num?)?.toInt() ?? 0,
    );
  }

  static const CheckinCalendar empty = CheckinCalendar(ladder: [], currentStreak: 0);
}

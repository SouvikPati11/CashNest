import 'package:flutter/foundation.dart';

/// A leaderboard ranking row (`GET /v1/leaderboard`). PII minimized to display
/// name + avatar.
@immutable
class LeaderboardEntry {
  const LeaderboardEntry({
    required this.rank,
    required this.name,
    required this.score,
    this.avatarUrl,
  });

  final int rank;
  final String name;
  final int score;
  final String? avatarUrl;

  static int _int(dynamic v) => (v as num?)?.toInt() ?? 0;

  factory LeaderboardEntry.fromJson(Map<String, dynamic> json) {
    final user = json['user'];
    final userMap = user is Map<String, dynamic> ? user : const <String, dynamic>{};
    return LeaderboardEntry(
      rank: _int(json['rank']),
      name: userMap['name'] as String? ?? json['name'] as String? ?? '—',
      score: _int(json['score']),
      avatarUrl: userMap['avatar_url'] as String? ?? json['avatar_url'] as String?,
    );
  }
}

/// The caller's own rank + score for a period (`GET /v1/leaderboard/me`).
@immutable
class LeaderboardMe {
  const LeaderboardMe({required this.rank, required this.score, this.period});

  final int rank;
  final int score;
  final String? period;

  static int _int(dynamic v) => (v as num?)?.toInt() ?? 0;

  factory LeaderboardMe.fromJson(Map<String, dynamic> json) {
    return LeaderboardMe(
      rank: _int(json['rank']),
      score: _int(json['score']),
      period: json['period'] as String?,
    );
  }
}

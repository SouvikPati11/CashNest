import 'package:flutter/foundation.dart';

/// A display segment of the spin wheel (weights/odds are never exposed).
@immutable
class SpinSegment {
  const SpinSegment({
    required this.id,
    required this.label,
    required this.colorHex,
    required this.position,
  });

  final String id;
  final String label;
  final String colorHex;
  final int position;

  factory SpinSegment.fromJson(Map<String, dynamic> json) {
    return SpinSegment(
      id: json['id'] as String? ?? '',
      label: json['label'] as String? ?? '',
      colorHex: json['color_hex'] as String? ?? '#CCCCCC',
      position: (json['position'] as num?)?.toInt() ?? 0,
    );
  }
}

/// Spin availability + wheel segments (`GET /v1/spin/status`).
@immutable
class SpinStatus {
  const SpinStatus({
    required this.spinsRemaining,
    required this.dailyLimit,
    required this.segments,
  });

  final int spinsRemaining;
  final int dailyLimit;
  final List<SpinSegment> segments;

  bool get canSpin => spinsRemaining > 0 && segments.isNotEmpty;

  factory SpinStatus.fromJson(Map<String, dynamic> json) {
    final rawSegments = json['segments'];
    final segments = rawSegments is List
        ? (rawSegments.whereType<Map<String, dynamic>>().map(SpinSegment.fromJson).toList()
          ..sort((a, b) => a.position.compareTo(b.position)))
        : <SpinSegment>[];
    return SpinStatus(
      spinsRemaining: (json['spins_remaining'] as num?)?.toInt() ?? 0,
      dailyLimit: (json['daily_limit'] as num?)?.toInt() ?? 0,
      segments: segments,
    );
  }

  SpinStatus copyWith({int? spinsRemaining, List<SpinSegment>? segments}) {
    return SpinStatus(
      spinsRemaining: spinsRemaining ?? this.spinsRemaining,
      dailyLimit: dailyLimit,
      segments: segments ?? this.segments,
    );
  }

  static const SpinStatus empty = SpinStatus(spinsRemaining: 0, dailyLimit: 0, segments: []);
}

import 'package:flutter/foundation.dart';

import 'withdraw_request.dart';

/// A single status-timeline event (`withdraw_history` row).
@immutable
class WithdrawEvent {
  const WithdrawEvent({
    required this.toStatus,
    this.fromStatus,
    this.note,
    this.createdAt,
  });

  final String toStatus;
  final String? fromStatus;
  final String? note;
  final DateTime? createdAt;

  static DateTime? _date(dynamic v) => v is String ? DateTime.tryParse(v) : null;

  factory WithdrawEvent.fromJson(Map<String, dynamic> json) {
    return WithdrawEvent(
      toStatus: json['to_status'] as String? ?? '',
      fromStatus: json['from_status'] as String?,
      note: json['note'] as String?,
      createdAt: _date(json['created_at']),
    );
  }
}

/// A withdrawal request plus its status timeline (`GET /v1/withdraw/{uuid}`).
@immutable
class WithdrawDetail {
  const WithdrawDetail({required this.request, this.history = const []});

  final WithdrawRequest request;
  final List<WithdrawEvent> history;

  factory WithdrawDetail.fromJson(Map<String, dynamic> json) {
    final rawHistory = json['history'];
    final history = rawHistory is List
        ? rawHistory
            .whereType<Map<String, dynamic>>()
            .map(WithdrawEvent.fromJson)
            .toList(growable: false)
        : const <WithdrawEvent>[];
    return WithdrawDetail(request: WithdrawRequest.fromJson(json), history: history);
  }
}

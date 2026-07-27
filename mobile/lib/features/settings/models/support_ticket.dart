import 'package:flutter/foundation.dart';

/// A support ticket summary (`GET /v1/support/tickets`).
@immutable
class SupportTicket {
  const SupportTicket({
    required this.uuid,
    required this.subject,
    required this.status,
    this.category,
    this.lastReplyAt,
    this.createdAt,
  });

  final String uuid;
  final String subject;
  final String status;
  final String? category;
  final DateTime? lastReplyAt;
  final DateTime? createdAt;

  bool get isOpen => status == 'open' || status == 'answered';

  static DateTime? _date(dynamic v) => v is String ? DateTime.tryParse(v) : null;

  factory SupportTicket.fromJson(Map<String, dynamic> json) {
    return SupportTicket(
      uuid: json['uuid'] as String? ?? '',
      subject: json['subject'] as String? ?? '',
      status: json['status'] as String? ?? 'open',
      category: json['category'] as String?,
      lastReplyAt: _date(json['last_reply_at']),
      createdAt: _date(json['created_at']),
    );
  }
}

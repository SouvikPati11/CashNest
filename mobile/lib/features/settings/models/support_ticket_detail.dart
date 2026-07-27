import 'package:flutter/foundation.dart';

import 'support_ticket.dart';

/// A single message in a support ticket thread.
@immutable
class SupportMessage {
  const SupportMessage({
    required this.senderType,
    required this.message,
    this.uuid,
    this.createdAt,
  });

  /// `user` | `agent` | `system`.
  final String senderType;
  final String message;
  final String? uuid;
  final DateTime? createdAt;

  bool get isFromUser => senderType == 'user';

  static DateTime? _date(dynamic v) => v is String ? DateTime.tryParse(v) : null;

  factory SupportMessage.fromJson(Map<String, dynamic> json) {
    return SupportMessage(
      senderType: json['sender_type'] as String? ?? 'agent',
      message: json['message'] as String? ?? '',
      uuid: json['uuid'] as String?,
      createdAt: _date(json['created_at']),
    );
  }
}

/// A support ticket thread (`GET /v1/support/tickets/{uuid}`).
@immutable
class SupportTicketDetail {
  const SupportTicketDetail({required this.ticket, this.messages = const []});

  final SupportTicket ticket;
  final List<SupportMessage> messages;

  factory SupportTicketDetail.fromJson(Map<String, dynamic> json) {
    final rawMessages = json['messages'];
    final messages = rawMessages is List
        ? rawMessages
            .whereType<Map<String, dynamic>>()
            .map(SupportMessage.fromJson)
            .toList(growable: false)
        : const <SupportMessage>[];
    return SupportTicketDetail(ticket: SupportTicket.fromJson(json), messages: messages);
  }
}

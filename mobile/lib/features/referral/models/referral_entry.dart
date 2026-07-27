import 'package:flutter/foundation.dart';

/// A referred user + status (`GET /v1/referral/list`), referee PII minimized.
@immutable
class ReferralEntry {
  const ReferralEntry({
    required this.refereeName,
    required this.status,
    this.joinedAt,
  });

  final String refereeName;
  final String status;
  final DateTime? joinedAt;

  bool get isQualified => status == 'qualified';

  static DateTime? _date(dynamic v) => v is String ? DateTime.tryParse(v) : null;

  factory ReferralEntry.fromJson(Map<String, dynamic> json) {
    return ReferralEntry(
      refereeName: json['referee_name'] as String? ?? '—',
      status: json['status'] as String? ?? 'pending',
      joinedAt: _date(json['joined_at']),
    );
  }
}

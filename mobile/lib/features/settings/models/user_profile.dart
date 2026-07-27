import 'package:flutter/foundation.dart';

/// The signed-in user's profile (`GET /v1/profile`).
@immutable
class UserProfile {
  const UserProfile({
    required this.uuid,
    required this.name,
    required this.email,
    this.avatarUrl,
    this.referralCode,
    this.countryCode,
    this.status = 'active',
    this.kycStatus,
    this.createdAt,
  });

  final String uuid;
  final String name;
  final String email;
  final String? avatarUrl;
  final String? referralCode;
  final String? countryCode;
  final String status;
  final String? kycStatus;
  final DateTime? createdAt;

  static DateTime? _date(dynamic v) => v is String ? DateTime.tryParse(v) : null;

  factory UserProfile.fromJson(Map<String, dynamic> json) {
    return UserProfile(
      uuid: json['uuid'] as String? ?? '',
      name: json['name'] as String? ?? '',
      email: json['email'] as String? ?? '',
      avatarUrl: json['avatar_url'] as String?,
      referralCode: json['referral_code'] as String?,
      countryCode: json['country_code'] as String?,
      status: json['status'] as String? ?? 'active',
      kycStatus: json['kyc_status'] as String?,
      createdAt: _date(json['created_at']),
    );
  }

  static const UserProfile empty = UserProfile(uuid: '', name: '', email: '');
}

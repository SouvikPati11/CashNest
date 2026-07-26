import 'package:flutter/foundation.dart';

/// Authenticated user profile returned by the auth endpoints.
@immutable
class AuthUser {
  const AuthUser({
    required this.uuid,
    required this.name,
    required this.email,
    this.avatarUrl,
    this.referralCode,
    this.status = 'active',
    this.isNew = false,
  });

  final String uuid;
  final String name;
  final String email;
  final String? avatarUrl;
  final String? referralCode;
  final String status;
  final bool isNew;

  factory AuthUser.fromJson(Map<String, dynamic> json) {
    return AuthUser(
      uuid: json['uuid'] as String? ?? '',
      name: json['name'] as String? ?? '',
      email: json['email'] as String? ?? '',
      avatarUrl: json['avatar_url'] as String?,
      referralCode: json['referral_code'] as String?,
      status: json['status'] as String? ?? 'active',
      isNew: json['is_new'] as bool? ?? false,
    );
  }
}

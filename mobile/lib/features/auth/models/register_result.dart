import 'package:flutter/foundation.dart';

import 'auth_user.dart';

/// Result of email registration — the created user plus whether verification is
/// required before tokens are issued.
@immutable
class RegisterResult {
  const RegisterResult({required this.user, required this.verificationRequired});

  final AuthUser user;
  final bool verificationRequired;

  factory RegisterResult.fromJson(Map<String, dynamic> json) {
    final user = json['user'];
    return RegisterResult(
      user: AuthUser.fromJson(user is Map<String, dynamic> ? user : const {}),
      verificationRequired: json['verification_required'] as bool? ?? true,
    );
  }
}

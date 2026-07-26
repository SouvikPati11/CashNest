import 'package:flutter/foundation.dart';

import 'auth_tokens.dart';
import 'auth_user.dart';

/// A completed authentication (user + token pair) from login / Google / verify.
@immutable
class AuthSession {
  const AuthSession({required this.user, required this.tokens});

  final AuthUser user;
  final AuthTokens tokens;

  factory AuthSession.fromJson(Map<String, dynamic> json) {
    final user = json['user'];
    final tokens = json['tokens'];
    return AuthSession(
      user: AuthUser.fromJson(user is Map<String, dynamic> ? user : const {}),
      tokens: AuthTokens.fromJson(tokens is Map<String, dynamic> ? tokens : const {}),
    );
  }
}

import 'package:flutter/foundation.dart';

import '../models/auth_user.dart';

/// High-level authentication status driving the router/guard.
enum AuthStatus { unknown, authenticated, unauthenticated }

/// Global auth state: the current status and (when known) the signed-in user.
@immutable
class AuthState {
  const AuthState({required this.status, this.user});

  const AuthState.unknown()
      : status = AuthStatus.unknown,
        user = null;

  const AuthState.unauthenticated()
      : status = AuthStatus.unauthenticated,
        user = null;

  const AuthState.authenticated(this.user) : status = AuthStatus.authenticated;

  final AuthStatus status;
  final AuthUser? user;

  bool get isAuthenticated => status == AuthStatus.authenticated;

  bool get isKnown => status != AuthStatus.unknown;
}

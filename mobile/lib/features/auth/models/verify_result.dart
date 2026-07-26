import 'package:flutter/foundation.dart';

import 'auth_tokens.dart';

/// Result of email verification — issues a token pair on success.
@immutable
class VerifyResult {
  const VerifyResult({required this.verified, required this.tokens});

  final bool verified;
  final AuthTokens tokens;

  factory VerifyResult.fromJson(Map<String, dynamic> json) {
    final tokens = json['tokens'];
    return VerifyResult(
      verified: json['verified'] as bool? ?? false,
      tokens: AuthTokens.fromJson(tokens is Map<String, dynamic> ? tokens : const {}),
    );
  }
}

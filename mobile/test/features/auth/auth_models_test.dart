import 'package:cashnest/features/auth/models/auth_session.dart';
import 'package:cashnest/features/auth/models/register_result.dart';
import 'package:cashnest/features/auth/models/verify_result.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('AuthSession.fromJson', () {
    test('parses user and tokens', () {
      final session = AuthSession.fromJson(const {
        'user': {'uuid': 'u_1', 'name': 'Asha', 'email': 'asha@x.io', 'is_new': false},
        'tokens': {'access_token': 'at', 'refresh_token': 'rt', 'expires_in': 1800},
      });

      expect(session.user.uuid, 'u_1');
      expect(session.user.name, 'Asha');
      expect(session.tokens.accessToken, 'at');
      expect(session.tokens.refreshToken, 'rt');
      expect(session.tokens.expiresIn, 1800);
      expect(session.tokens.isValid, isTrue);
    });

    test('tolerates missing fields', () {
      final session = AuthSession.fromJson(const {});
      expect(session.user.uuid, '');
      expect(session.tokens.isValid, isFalse);
    });
  });

  test('RegisterResult.fromJson parses verification flag', () {
    final result = RegisterResult.fromJson(const {
      'user': {'uuid': 'u_2', 'email': 'new@x.io'},
      'verification_required': true,
    });
    expect(result.user.uuid, 'u_2');
    expect(result.verificationRequired, isTrue);
  });

  test('VerifyResult.fromJson parses verified + tokens', () {
    final result = VerifyResult.fromJson(const {
      'verified': true,
      'tokens': {'access_token': 'at', 'refresh_token': 'rt'},
    });
    expect(result.verified, isTrue);
    expect(result.tokens.accessToken, 'at');
  });
}

import 'package:cashnest/features/auth/validation/auth_validators.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('AuthValidators.email', () {
    test('accepts valid emails', () {
      expect(AuthValidators.email('user@example.com'), isNull);
    });
    test('rejects empty and malformed', () {
      expect(AuthValidators.email(''), AuthFieldError.required);
      expect(AuthValidators.email('not-an-email'), AuthFieldError.invalidEmail);
    });
  });

  group('AuthValidators.password', () {
    test('accepts strong passwords', () {
      expect(AuthValidators.password('secret1pass'), isNull);
    });
    test('rejects weak passwords', () {
      expect(AuthValidators.password(''), AuthFieldError.required);
      expect(AuthValidators.password('short1'), AuthFieldError.weakPassword);
      expect(AuthValidators.password('allletters'), AuthFieldError.weakPassword);
      expect(AuthValidators.password('12345678'), AuthFieldError.weakPassword);
    });
  });

  group('AuthValidators.name', () {
    test('accepts and rejects', () {
      expect(AuthValidators.name('Asha'), isNull);
      expect(AuthValidators.name(''), AuthFieldError.required);
      expect(AuthValidators.name('A'), AuthFieldError.nameTooShort);
    });
  });

  group('AuthValidators.otp', () {
    test('accepts 6 digits, rejects others', () {
      expect(AuthValidators.otp('123456'), isNull);
      expect(AuthValidators.otp(''), AuthFieldError.required);
      expect(AuthValidators.otp('12ab56'), AuthFieldError.invalidOtp);
      expect(AuthValidators.otp('123'), AuthFieldError.invalidOtp);
    });
  });
}

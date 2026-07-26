/// Field validation errors surfaced by [AuthValidators]. Screens map these to
/// localized messages, keeping validation logic pure and unit-testable.
enum AuthFieldError {
  required,
  invalidEmail,
  weakPassword,
  nameTooShort,
  invalidOtp,
}

/// Pure form validators for the authentication screens.
abstract final class AuthValidators {
  const AuthValidators._();

  static final RegExp _email = RegExp(r'^[\w.\-+]+@([\w\-]+\.)+[\w\-]{2,}$');
  static final RegExp _hasLetter = RegExp(r'[A-Za-z]');
  static final RegExp _hasDigit = RegExp(r'\d');

  static AuthFieldError? requiredField(String? value) {
    return (value == null || value.trim().isEmpty) ? AuthFieldError.required : null;
  }

  static AuthFieldError? email(String? value) {
    final trimmed = value?.trim() ?? '';
    if (trimmed.isEmpty) {
      return AuthFieldError.required;
    }
    return _email.hasMatch(trimmed) ? null : AuthFieldError.invalidEmail;
  }

  static AuthFieldError? password(String? value) {
    final password = value ?? '';
    if (password.isEmpty) {
      return AuthFieldError.required;
    }
    final strong = password.length >= 8 &&
        password.length <= 72 &&
        _hasLetter.hasMatch(password) &&
        _hasDigit.hasMatch(password);
    return strong ? null : AuthFieldError.weakPassword;
  }

  static AuthFieldError? name(String? value) {
    final trimmed = value?.trim() ?? '';
    if (trimmed.isEmpty) {
      return AuthFieldError.required;
    }
    return (trimmed.length >= 2 && trimmed.length <= 120) ? null : AuthFieldError.nameTooShort;
  }

  static AuthFieldError? otp(String? value) {
    final trimmed = value?.trim() ?? '';
    if (trimmed.isEmpty) {
      return AuthFieldError.required;
    }
    return RegExp(r'^\d{6}$').hasMatch(trimmed) ? null : AuthFieldError.invalidOtp;
  }
}

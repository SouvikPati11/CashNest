import 'package:flutter/widgets.dart';

import '../validation/auth_validators.dart';

/// Feature-scoped localization for the auth module (English + Bangla).
///
/// Kept inside the feature so the shared foundation localization is not
/// modified. Resolves strings from the active [Locale].
class AuthStrings {
  const AuthStrings(this._values);

  final Map<String, String> _values;

  static AuthStrings of(BuildContext context) {
    final code = Localizations.localeOf(context).languageCode;
    return AuthStrings(code == 'bn' ? _bn : _en);
  }

  String _t(String key) => _values[key] ?? key;

  String get loginTitle => _t('loginTitle');
  String get loginSubtitle => _t('loginSubtitle');
  String get registerTitle => _t('registerTitle');
  String get registerSubtitle => _t('registerSubtitle');
  String get name => _t('name');
  String get email => _t('email');
  String get password => _t('password');
  String get referralOptional => _t('referralOptional');
  String get signIn => _t('signIn');
  String get createAccount => _t('createAccount');
  String get continueWithGoogle => _t('continueWithGoogle');
  String get orDivider => _t('orDivider');
  String get noAccountPrompt => _t('noAccountPrompt');
  String get haveAccountPrompt => _t('haveAccountPrompt');
  String get forgotPassword => _t('forgotPassword');
  String get verifyTitle => _t('verifyTitle');
  String get verifySubtitle => _t('verifySubtitle');
  String get otpLabel => _t('otpLabel');
  String get verify => _t('verify');
  String get forgotTitle => _t('forgotTitle');
  String get forgotSubtitle => _t('forgotSubtitle');
  String get sendResetLink => _t('sendResetLink');
  String get backToLogin => _t('backToLogin');
  String get logout => _t('logout');
  String get resetLinkSent => _t('resetLinkSent');
  String get googleCancelled => _t('googleCancelled');

  String errorFor(AuthFieldError error) {
    return switch (error) {
      AuthFieldError.required => _t('errRequired'),
      AuthFieldError.invalidEmail => _t('errInvalidEmail'),
      AuthFieldError.weakPassword => _t('errWeakPassword'),
      AuthFieldError.nameTooShort => _t('errNameTooShort'),
      AuthFieldError.invalidOtp => _t('errInvalidOtp'),
    };
  }

  static const Map<String, String> _en = {
    'loginTitle': 'Welcome back',
    'loginSubtitle': 'Sign in to continue to CashNest',
    'registerTitle': 'Create your account',
    'registerSubtitle': 'Start earning rewards with CashNest',
    'name': 'Full name',
    'email': 'Email',
    'password': 'Password',
    'referralOptional': 'Referral code (optional)',
    'signIn': 'Sign in',
    'createAccount': 'Create account',
    'continueWithGoogle': 'Continue with Google',
    'orDivider': 'OR',
    'noAccountPrompt': "Don't have an account? Register",
    'haveAccountPrompt': 'Already have an account? Sign in',
    'forgotPassword': 'Forgot password?',
    'verifyTitle': 'Verify your email',
    'verifySubtitle': 'Enter the 6-digit code we sent to your email.',
    'otpLabel': 'Verification code',
    'verify': 'Verify',
    'forgotTitle': 'Reset your password',
    'forgotSubtitle': 'Enter your email and we will send reset instructions.',
    'sendResetLink': 'Send reset link',
    'backToLogin': 'Back to sign in',
    'logout': 'Log out',
    'resetLinkSent': 'If the email exists, reset instructions have been sent.',
    'googleCancelled': 'Google sign-in was cancelled.',
    'errRequired': 'This field is required.',
    'errInvalidEmail': 'Enter a valid email address.',
    'errWeakPassword': 'Use 8+ characters with a letter and a number.',
    'errNameTooShort': 'Enter your full name.',
    'errInvalidOtp': 'Enter the 6-digit code.',
  };

  static const Map<String, String> _bn = {
    'loginTitle': 'আবার স্বাগতম',
    'loginSubtitle': 'চালিয়ে যেতে ক্যাশনেস্টে সাইন ইন করুন',
    'registerTitle': 'আপনার অ্যাকাউন্ট তৈরি করুন',
    'registerSubtitle': 'ক্যাশনেস্টের সাথে রিওয়ার্ড আয় শুরু করুন',
    'name': 'পুরো নাম',
    'email': 'ইমেইল',
    'password': 'পাসওয়ার্ড',
    'referralOptional': 'রেফারেল কোড (ঐচ্ছিক)',
    'signIn': 'সাইন ইন',
    'createAccount': 'অ্যাকাউন্ট তৈরি করুন',
    'continueWithGoogle': 'গুগল দিয়ে চালিয়ে যান',
    'orDivider': 'অথবা',
    'noAccountPrompt': 'অ্যাকাউন্ট নেই? নিবন্ধন করুন',
    'haveAccountPrompt': 'ইতিমধ্যে অ্যাকাউন্ট আছে? সাইন ইন করুন',
    'forgotPassword': 'পাসওয়ার্ড ভুলে গেছেন?',
    'verifyTitle': 'আপনার ইমেইল যাচাই করুন',
    'verifySubtitle': 'আপনার ইমেইলে পাঠানো ৬-সংখ্যার কোড লিখুন।',
    'otpLabel': 'যাচাইকরণ কোড',
    'verify': 'যাচাই করুন',
    'forgotTitle': 'পাসওয়ার্ড রিসেট করুন',
    'forgotSubtitle': 'আপনার ইমেইল দিন, আমরা রিসেট নির্দেশনা পাঠাব।',
    'sendResetLink': 'রিসেট লিঙ্ক পাঠান',
    'backToLogin': 'সাইন ইনে ফিরে যান',
    'logout': 'লগ আউট',
    'resetLinkSent': 'ইমেইলটি থাকলে, রিসেট নির্দেশনা পাঠানো হয়েছে।',
    'googleCancelled': 'গুগল সাইন-ইন বাতিল করা হয়েছে।',
    'errRequired': 'এই ঘরটি আবশ্যক।',
    'errInvalidEmail': 'সঠিক ইমেইল ঠিকানা লিখুন।',
    'errWeakPassword': 'একটি অক্ষর ও সংখ্যাসহ ৮+ অক্ষর ব্যবহার করুন।',
    'errNameTooShort': 'আপনার পুরো নাম লিখুন।',
    'errInvalidOtp': '৬-সংখ্যার কোড লিখুন।',
  };
}

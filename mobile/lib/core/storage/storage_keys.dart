/// Canonical keys for secure storage and preferences (avoids typo drift).
abstract final class StorageKeys {
  const StorageKeys._();

  // Secure storage (sensitive).
  static const String accessToken = 'access_token';
  static const String refreshToken = 'refresh_token';

  // Preferences (non-sensitive).
  static const String themeMode = 'theme_mode';
  static const String locale = 'locale';
  static const String onboardingSeen = 'onboarding_seen';
}

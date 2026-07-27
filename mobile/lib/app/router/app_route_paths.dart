/// Single source of truth for every route path + name in the integrated app.
///
/// The five shell paths back the bottom navigation; the rest are full-screen
/// routes pushed above the shell (or the pre-auth flow).
abstract final class AppRoutePaths {
  const AppRoutePaths._();

  // Pre-auth flow.
  static const String splash = '/';
  static const String login = '/login';
  static const String register = '/register';
  static const String verify = '/verify';
  static const String forgot = '/forgot';

  // Bottom-navigation shell tabs.
  static const String home = '/home';
  static const String rewards = '/rewards';
  static const String wallet = '/wallet';
  static const String earn = '/earn';
  static const String settings = '/settings';

  // Full-screen feature routes (above the shell).
  static const String referral = '/referral';
  static const String withdraw = '/withdraw';
  static const String notifications = '/notifications';
  static const String error = '/error';

  // Route names.
  static const String splashName = 'splash';
  static const String loginName = 'login';
  static const String registerName = 'register';
  static const String verifyName = 'verify';
  static const String forgotName = 'forgot';
  static const String homeName = 'home';
  static const String rewardsName = 'rewards';
  static const String walletName = 'wallet';
  static const String earnName = 'earn';
  static const String settingsName = 'settings';
  static const String referralName = 'referral';
  static const String withdrawName = 'withdraw';
  static const String notificationsName = 'notifications';
  static const String errorName = 'error';
}

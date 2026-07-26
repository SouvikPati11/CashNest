/// Auth route paths + names. Shares the foundation's splash/home/error paths
/// (see `core/router/app_routes.dart`).
abstract final class AuthRoutes {
  const AuthRoutes._();

  static const String login = '/login';
  static const String register = '/register';
  static const String verify = '/verify';
  static const String forgot = '/forgot';

  static const String loginName = 'login';
  static const String registerName = 'register';
  static const String verifyName = 'verify';
  static const String forgotName = 'forgot';

  /// Routes reachable while unauthenticated.
  static const Set<String> paths = {login, register, verify, forgot};

  static bool isAuthRoute(String location) => paths.contains(location);
}

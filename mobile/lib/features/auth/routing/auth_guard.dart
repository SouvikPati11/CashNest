import '../../../core/router/app_routes.dart';
import '../application/auth_state.dart';
import 'auth_routes.dart';

/// Pure authentication redirect (route guard) logic.
///
/// Extracted as a plain function so it is unit-testable independently of
/// GoRouter/BuildContext:
///  - unknown  → hold on splash while the session is restored
///  - unauthenticated → only auth routes allowed; everything else → login
///  - authenticated  → splash/auth routes bounce to home; everything else stays
String? authRedirect({required AuthStatus status, required String location}) {
  final onSplash = location == AppRoutes.splash;
  final onAuthRoute = AuthRoutes.isAuthRoute(location);

  switch (status) {
    case AuthStatus.unknown:
      return onSplash ? null : AppRoutes.splash;
    case AuthStatus.unauthenticated:
      return onAuthRoute ? null : AuthRoutes.login;
    case AuthStatus.authenticated:
      return (onSplash || onAuthRoute) ? AppRoutes.home : null;
  }
}

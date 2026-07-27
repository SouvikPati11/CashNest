import 'package:cashnest/app/router/app_route_paths.dart';
import 'package:cashnest/core/router/app_routes.dart';
import 'package:cashnest/features/auth/application/auth_state.dart';
import 'package:cashnest/features/auth/routing/auth_guard.dart';
import 'package:cashnest/features/auth/routing/auth_routes.dart';
import 'package:flutter_test/flutter_test.dart';

/// Integration checks: the guard used by the integrated router protects every
/// feature route, and the router's path constants align with the guard's.
void main() {
  const protectedRoutes = [
    AppRoutePaths.home,
    AppRoutePaths.rewards,
    AppRoutePaths.wallet,
    AppRoutePaths.earn,
    AppRoutePaths.settings,
    AppRoutePaths.referral,
    AppRoutePaths.withdraw,
    AppRoutePaths.notifications,
  ];

  const authRoutes = [
    AppRoutePaths.login,
    AppRoutePaths.register,
    AppRoutePaths.verify,
    AppRoutePaths.forgot,
  ];

  test('route constants align with the guard constants', () {
    expect(AppRoutePaths.splash, AppRoutes.splash);
    expect(AppRoutePaths.home, AppRoutes.home);
    expect(AppRoutePaths.login, AuthRoutes.login);
    expect(AppRoutePaths.register, AuthRoutes.register);
    expect(AppRoutePaths.verify, AuthRoutes.verify);
    expect(AppRoutePaths.forgot, AuthRoutes.forgot);
  });

  test('unauthenticated users are redirected to login from every feature route', () {
    for (final route in protectedRoutes) {
      expect(
        authRedirect(status: AuthStatus.unauthenticated, location: route),
        AuthRoutes.login,
        reason: '$route should redirect to login when unauthenticated',
      );
    }
  });

  test('authenticated users may stay on every feature route', () {
    for (final route in protectedRoutes) {
      expect(
        authRedirect(status: AuthStatus.authenticated, location: route),
        isNull,
        reason: '$route should be reachable when authenticated',
      );
    }
  });

  test('auth routes stay reachable while unauthenticated', () {
    for (final route in authRoutes) {
      expect(authRedirect(status: AuthStatus.unauthenticated, location: route), isNull);
    }
  });

  test('authenticated users are bounced from splash and auth routes to home', () {
    expect(authRedirect(status: AuthStatus.authenticated, location: AppRoutePaths.splash), AppRoutePaths.home);
    for (final route in authRoutes) {
      expect(authRedirect(status: AuthStatus.authenticated, location: route), AppRoutePaths.home);
    }
  });

  test('unknown status holds on splash while the session restores', () {
    expect(authRedirect(status: AuthStatus.unknown, location: AppRoutePaths.wallet), AppRoutePaths.splash);
    expect(authRedirect(status: AuthStatus.unknown, location: AppRoutePaths.splash), isNull);
  });
}

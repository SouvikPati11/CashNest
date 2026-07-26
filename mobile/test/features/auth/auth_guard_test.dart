import 'package:cashnest/core/router/app_routes.dart';
import 'package:cashnest/features/auth/application/auth_state.dart';
import 'package:cashnest/features/auth/routing/auth_guard.dart';
import 'package:cashnest/features/auth/routing/auth_routes.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('authRedirect', () {
    test('unknown status holds on splash', () {
      expect(
        authRedirect(status: AuthStatus.unknown, location: AppRoutes.home),
        AppRoutes.splash,
      );
      expect(
        authRedirect(status: AuthStatus.unknown, location: AppRoutes.splash),
        isNull,
      );
    });

    test('unauthenticated is sent to login from protected routes', () {
      expect(
        authRedirect(status: AuthStatus.unauthenticated, location: AppRoutes.home),
        AuthRoutes.login,
      );
      expect(
        authRedirect(status: AuthStatus.unauthenticated, location: AppRoutes.splash),
        AuthRoutes.login,
      );
    });

    test('unauthenticated may stay on auth routes', () {
      expect(
        authRedirect(status: AuthStatus.unauthenticated, location: AuthRoutes.register),
        isNull,
      );
      expect(
        authRedirect(status: AuthStatus.unauthenticated, location: AuthRoutes.forgot),
        isNull,
      );
    });

    test('authenticated bounces away from splash/auth to home', () {
      expect(
        authRedirect(status: AuthStatus.authenticated, location: AppRoutes.splash),
        AppRoutes.home,
      );
      expect(
        authRedirect(status: AuthStatus.authenticated, location: AuthRoutes.login),
        AppRoutes.home,
      );
      expect(
        authRedirect(status: AuthStatus.authenticated, location: AppRoutes.home),
        isNull,
      );
    });
  });
}

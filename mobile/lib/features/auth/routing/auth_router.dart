import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/router/app_routes.dart';
import '../../error/error_screen.dart';
import '../../home/home_screen.dart';
import '../application/auth_state.dart';
import '../presentation/auth_splash_screen.dart';
import '../presentation/email_verification_screen.dart';
import '../presentation/forgot_password_screen.dart';
import '../presentation/login_screen.dart';
import '../presentation/register_screen.dart';
import '../providers/auth_providers.dart';
import 'auth_guard.dart';
import 'auth_routes.dart';

/// Auth-aware application router.
///
/// A NEW provider (the foundation `goRouterProvider` is left untouched): it wires
/// the splash → auth → home flow and applies [authRedirect] as the route guard,
/// reacting to auth-state changes. Integration overrides `goRouterProvider` with
/// this provider (documented in the module README).
final authRouterProvider = Provider<GoRouter>((ref) {
  final refresh = ValueNotifier<int>(0);
  ref.onDispose(refresh.dispose);
  ref.listen<AuthState>(authControllerProvider, (_, __) => refresh.value++);

  return GoRouter(
    initialLocation: AppRoutes.splash,
    refreshListenable: refresh,
    redirect: (context, state) => authRedirect(
      status: ref.read(authControllerProvider).status,
      location: state.matchedLocation,
    ),
    routes: [
      GoRoute(
        path: AppRoutes.splash,
        name: AppRoutes.splashName,
        builder: (context, state) => const AuthSplashScreen(),
      ),
      GoRoute(
        path: AuthRoutes.login,
        name: AuthRoutes.loginName,
        builder: (context, state) => const LoginScreen(),
      ),
      GoRoute(
        path: AuthRoutes.register,
        name: AuthRoutes.registerName,
        builder: (context, state) => const RegisterScreen(),
      ),
      GoRoute(
        path: AuthRoutes.verify,
        name: AuthRoutes.verifyName,
        builder: (context, state) =>
            EmailVerificationScreen(email: state.extra as String? ?? ''),
      ),
      GoRoute(
        path: AuthRoutes.forgot,
        name: AuthRoutes.forgotName,
        builder: (context, state) => const ForgotPasswordScreen(),
      ),
      GoRoute(
        path: AppRoutes.home,
        name: AppRoutes.homeName,
        builder: (context, state) => const HomeScreen(),
      ),
      GoRoute(
        path: AppRoutes.error,
        name: AppRoutes.errorName,
        builder: (context, state) => ErrorScreen(message: state.extra as String?),
      ),
    ],
    errorBuilder: (context, state) => ErrorScreen(message: state.error?.toString()),
  );
});

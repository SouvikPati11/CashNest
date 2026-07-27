import 'package:flutter/widgets.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../features/auth/application/auth_state.dart';
import '../../features/auth/presentation/auth_splash_screen.dart';
import '../../features/auth/presentation/email_verification_screen.dart';
import '../../features/auth/presentation/forgot_password_screen.dart';
import '../../features/auth/presentation/login_screen.dart';
import '../../features/auth/presentation/register_screen.dart';
import '../../core/observability/analytics_route_observer.dart';
import '../../features/auth/providers/auth_providers.dart';
import '../../features/auth/routing/auth_guard.dart';
import '../../features/error/error_screen.dart';
import '../../features/home/presentation/home_dashboard_screen.dart';
import '../../features/notification/presentation/notification_screen.dart';
import '../../features/offerwall/presentation/offerwall_screen.dart';
import '../../features/referral/presentation/referral_screen.dart';
import '../../features/rewards/presentation/rewards_screen.dart';
import '../../features/settings/presentation/settings_screen.dart';
import '../../features/wallet/presentation/wallet_screen.dart';
import '../../features/withdraw/presentation/withdraw_screen.dart';
import '../di/providers.dart';
import 'app_route_paths.dart';
import 'home_shell.dart';

final _rootNavigatorKey = GlobalKey<NavigatorState>(debugLabel: 'root');

/// The integrated application router.
///
/// Wires the splash → auth → home flow, the five-tab bottom-navigation shell,
/// and the full-screen feature routes, all behind the authentication guard
/// ([authRedirect]). Reacts to auth-state changes via [refreshListenable].
final appRouterProvider = Provider<GoRouter>((ref) {
  final refresh = ValueNotifier<int>(0);
  ref.onDispose(refresh.dispose);
  ref.listen<AuthState>(authControllerProvider, (_, __) => refresh.value++);

  // Top-level routes render on the root navigator (above the shell) by default.
  GoRoute fullScreen(String path, String name, Widget screen) {
    return GoRoute(
      path: path,
      name: name,
      builder: (context, state) => screen,
    );
  }

  return GoRouter(
    navigatorKey: _rootNavigatorKey,
    initialLocation: AppRoutePaths.splash,
    refreshListenable: refresh,
    observers: [AnalyticsRouteObserver(ref.watch(analyticsServiceProvider))],
    redirect: (context, state) => authRedirect(
      status: ref.read(authControllerProvider).status,
      location: state.matchedLocation,
    ),
    routes: [
      GoRoute(
        path: AppRoutePaths.splash,
        name: AppRoutePaths.splashName,
        builder: (context, state) => const AuthSplashScreen(),
      ),
      GoRoute(
        path: AppRoutePaths.login,
        name: AppRoutePaths.loginName,
        builder: (context, state) => const LoginScreen(),
      ),
      GoRoute(
        path: AppRoutePaths.register,
        name: AppRoutePaths.registerName,
        builder: (context, state) => const RegisterScreen(),
      ),
      GoRoute(
        path: AppRoutePaths.verify,
        name: AppRoutePaths.verifyName,
        builder: (context, state) =>
            EmailVerificationScreen(email: state.extra as String? ?? ''),
      ),
      GoRoute(
        path: AppRoutePaths.forgot,
        name: AppRoutePaths.forgotName,
        builder: (context, state) => const ForgotPasswordScreen(),
      ),

      // Bottom-navigation shell (five primary tabs).
      StatefulShellRoute.indexedStack(
        builder: (context, state, navigationShell) =>
            HomeShell(navigationShell: navigationShell),
        branches: [
          StatefulShellBranch(routes: [
            GoRoute(
              path: AppRoutePaths.home,
              name: AppRoutePaths.homeName,
              builder: (context, state) => const HomeDashboardScreen(),
            ),
          ]),
          StatefulShellBranch(routes: [
            GoRoute(
              path: AppRoutePaths.rewards,
              name: AppRoutePaths.rewardsName,
              builder: (context, state) => const RewardsScreen(),
            ),
          ]),
          StatefulShellBranch(routes: [
            GoRoute(
              path: AppRoutePaths.wallet,
              name: AppRoutePaths.walletName,
              builder: (context, state) => const WalletScreen(),
            ),
          ]),
          StatefulShellBranch(routes: [
            GoRoute(
              path: AppRoutePaths.earn,
              name: AppRoutePaths.earnName,
              builder: (context, state) => const OfferwallScreen(),
            ),
          ]),
          StatefulShellBranch(routes: [
            GoRoute(
              path: AppRoutePaths.settings,
              name: AppRoutePaths.settingsName,
              builder: (context, state) => const SettingsScreen(),
            ),
          ]),
        ],
      ),

      // Full-screen feature routes (above the shell).
      fullScreen(AppRoutePaths.referral, AppRoutePaths.referralName, const ReferralScreen()),
      fullScreen(AppRoutePaths.withdraw, AppRoutePaths.withdrawName, const WithdrawScreen()),
      fullScreen(
          AppRoutePaths.notifications, AppRoutePaths.notificationsName, const NotificationScreen()),
      GoRoute(
        path: AppRoutePaths.error,
        name: AppRoutePaths.errorName,
        builder: (context, state) => ErrorScreen(message: state.extra as String?),
      ),
    ],
    errorBuilder: (context, state) => ErrorScreen(message: state.error?.toString()),
  );
});

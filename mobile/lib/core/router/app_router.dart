import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../features/error/error_screen.dart';
import '../../features/home/home_screen.dart';
import '../../features/splash/splash_screen.dart';
import 'app_routes.dart';
import 'app_startup_provider.dart';

/// Application router.
///
/// Redirect logic acts as the route guard: it holds the user on the splash while
/// the app warms up, then routes to home. Auth-protected routes (added by
/// feature modules) plug into the marked seam below.
final goRouterProvider = Provider<GoRouter>((ref) {
  final refresh = ValueNotifier<int>(0);
  ref.onDispose(refresh.dispose);
  ref.listen<bool>(appStartupProvider, (_, __) => refresh.value++);

  return GoRouter(
    initialLocation: AppRoutes.splash,
    refreshListenable: refresh,
    redirect: (context, state) {
      final isReady = ref.read(appStartupProvider);
      final atSplash = state.matchedLocation == AppRoutes.splash;

      // Warm-up guard: stay on splash until the app is ready.
      if (!isReady) {
        return atSplash ? null : AppRoutes.splash;
      }

      // Once ready, leave the splash for the landing route.
      if (atSplash) {
        return AppRoutes.home;
      }

      // Auth guard seam: when feature modules add protected routes, check the
      // session here and redirect unauthenticated users to sign-in.
      return null;
    },
    routes: [
      GoRoute(
        path: AppRoutes.splash,
        name: AppRoutes.splashName,
        builder: (context, state) => const SplashScreen(),
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

import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/localization/app_localizations.dart';
import '../core/theme/app_theme.dart';
import 'di/providers.dart';
import 'lifecycle/app_lifecycle_reactor.dart';
import 'router/app_router.dart';

/// Root application widget.
///
/// Wires the Material 3 light/dark themes, runtime theme mode, localization
/// (English + Bangla), and the GoRouter configuration together.
class CashNestApp extends ConsumerWidget {
  const CashNestApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final router = ref.watch(appRouterProvider);
    final themeMode = ref.watch(themeModeControllerProvider);
    final locale = ref.watch(localeControllerProvider);

    // Apply the Admin-configured default theme mode (Light/Dark/System) unless
    // the user has chosen their own. Colours/typography stay app-defined; this
    // fetch fails gracefully (no override) when the API is unavailable.
    ref.listen(serverThemeModeProvider, (_, next) {
      ref.read(themeModeControllerProvider.notifier).applyServerDefault(next.valueOrNull);
    });

    return AppLifecycleReactor(
      child: MaterialApp.router(
        title: 'CashNest',
        debugShowCheckedModeBanner: false,
        theme: AppTheme.light,
        darkTheme: AppTheme.dark,
        themeMode: themeMode,
        locale: locale,
        supportedLocales: AppLocalizations.supportedLocales,
        localizationsDelegates: const [
          AppLocalizations.delegate,
          GlobalMaterialLocalizations.delegate,
          GlobalWidgetsLocalizations.delegate,
          GlobalCupertinoLocalizations.delegate,
        ],
        routerConfig: router,
      ),
    );
  }
}

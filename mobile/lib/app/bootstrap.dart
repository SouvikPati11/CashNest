import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hive_flutter/hive_flutter.dart';

import '../core/config/app_config.dart';
import '../core/constants/app_constants.dart';
import '../core/error/global_error_handler.dart';
import '../core/firebase/analytics_service.dart';
import '../core/firebase/firebase_initializer.dart';
import '../core/firebase/messaging_background_handler.dart';
import '../core/logging/app_logger.dart';
import '../core/observability/crash_reporter.dart';
import '../core/storage/hive_cache_service.dart';
import '../core/storage/preference_manager.dart';
import 'app.dart';
import 'di/providers.dart';

/// Application bootstrap: initializes infrastructure, wires provider overrides,
/// and launches the app inside a guarded error zone with crash reporting.
Future<void> bootstrap() async {
  final config = AppConfig.resolve();
  final logger = AppLogger(verbose: config.enableNetworkLogging);
  final CrashReporter crashReporter = LoggingCrashReporter(logger);
  // Collect crashes only in release-like builds; keep debug output local.
  await crashReporter.setEnabled(config.environment.isProduction);

  await runGuarded(
    logger,
    () async {
      WidgetsFlutterBinding.ensureInitialized();

      // Local storage (Hive) — task 15.
      await Hive.initFlutter();
      final cacheBox = await Hive.openBox<String>(AppConstants.cacheBoxName);
      final prefsBox = await Hive.openBox<dynamic>(AppConstants.prefsBoxName);

      final preferences = PreferenceManager(prefsBox);
      final cache = HiveCacheService(cacheBox);

      // Firebase (guarded — skipped gracefully when unconfigured) — task 1/16.
      final firebase = FirebaseInitializer(logger);
      final firebaseReady = await firebase.initialize();
      final AnalyticsService analytics =
          firebaseReady ? FirebaseAnalyticsService() : NoopAnalyticsService(logger);

      // Register the FCM background/terminated handler once Firebase is ready.
      if (firebaseReady) {
        try {
          FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler);
        } catch (error, stack) {
          logger.warn('Could not register FCM background handler.', error, stack);
        }
      }

      runApp(
        ProviderScope(
          overrides: [
            appConfigProvider.overrideWithValue(config),
            appLoggerProvider.overrideWithValue(logger),
            preferenceManagerProvider.overrideWithValue(preferences),
            hiveCacheProvider.overrideWithValue(cache),
            analyticsServiceProvider.overrideWithValue(analytics),
            crashReporterProvider.overrideWithValue(crashReporter),
            firebaseReadyProvider.overrideWithValue(firebaseReady),
          ],
          child: const CashNestApp(),
        ),
      );
    },
    crashReporter: crashReporter,
  );
}

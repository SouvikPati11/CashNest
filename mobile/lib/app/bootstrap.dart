import 'package:flutter/widgets.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hive_flutter/hive_flutter.dart';

import '../core/config/app_config.dart';
import '../core/constants/app_constants.dart';
import '../core/error/global_error_handler.dart';
import '../core/firebase/analytics_service.dart';
import '../core/firebase/firebase_initializer.dart';
import '../core/logging/app_logger.dart';
import '../core/storage/hive_cache_service.dart';
import '../core/storage/preference_manager.dart';
import 'app.dart';
import 'di/providers.dart';

/// Application bootstrap: initializes infrastructure, wires provider overrides,
/// and launches the app inside a guarded error zone.
Future<void> bootstrap() async {
  final config = AppConfig.resolve();
  final logger = AppLogger(verbose: config.enableNetworkLogging);

  await runGuarded(logger, () async {
    WidgetsFlutterBinding.ensureInitialized();

    // Local storage.
    await Hive.initFlutter();
    final cacheBox = await Hive.openBox<String>(AppConstants.cacheBoxName);
    final prefsBox = await Hive.openBox<dynamic>(AppConstants.prefsBoxName);

    final preferences = PreferenceManager(prefsBox);
    final cache = HiveCacheService(cacheBox);

    // Firebase (guarded — skipped gracefully when unconfigured).
    final firebase = FirebaseInitializer(logger);
    final firebaseReady = await firebase.initialize();
    final AnalyticsService analytics =
        firebaseReady ? FirebaseAnalyticsService() : NoopAnalyticsService(logger);

    runApp(
      ProviderScope(
        overrides: [
          appConfigProvider.overrideWithValue(config),
          appLoggerProvider.overrideWithValue(logger),
          preferenceManagerProvider.overrideWithValue(preferences),
          hiveCacheProvider.overrideWithValue(cache),
          analyticsServiceProvider.overrideWithValue(analytics),
        ],
        child: const CashNestApp(),
      ),
    );
  });
}

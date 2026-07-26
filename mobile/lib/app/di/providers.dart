import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/config/app_config.dart';
import '../../core/connectivity/connectivity_service.dart';
import '../../core/firebase/analytics_service.dart';
import '../../core/localization/locale_controller.dart';
import '../../core/logging/app_logger.dart';
import '../../core/network/api_client.dart';
import '../../core/network/dio_factory.dart';
import '../../core/platform/app_info_service.dart';
import '../../core/storage/hive_cache_service.dart';
import '../../core/storage/preference_manager.dart';
import '../../core/storage/secure_storage_service.dart';
import '../../core/theme/theme_mode_controller.dart';

/// Central Riverpod provider registration for the foundation.
///
/// Providers that depend on async initialization (Hive boxes, analytics,
/// app info) are declared here but OVERRIDDEN in [bootstrap] with concrete,
/// pre-initialized instances. Accessing them before bootstrap overrides them is
/// a programming error and throws.

/// Marker used by not-yet-initialized providers.
Never _uninitialized(String name) =>
    throw StateError('$name was read before bootstrap initialized it.');

// ── Configuration & diagnostics ────────────────────────────────────────────

final appConfigProvider = Provider<AppConfig>((ref) => AppConfig.resolve());

final appLoggerProvider = Provider<AppLogger>(
  (ref) => AppLogger(verbose: ref.watch(appConfigProvider).enableNetworkLogging),
);

// ── Storage (overridden in bootstrap once boxes are open) ───────────────────

final secureStorageProvider = Provider<SecureStorageService>(
  (ref) => SecureStorageService(),
);

final preferenceManagerProvider = Provider<PreferenceManager>(
  (ref) => _uninitialized('preferenceManagerProvider'),
);

final hiveCacheProvider = Provider<HiveCacheService>(
  (ref) => _uninitialized('hiveCacheProvider'),
);

// ── Connectivity & platform ────────────────────────────────────────────────

final connectivityServiceProvider = Provider<ConnectivityService>(
  (ref) => ConnectivityService(),
);

final connectivityStatusProvider = StreamProvider<bool>(
  (ref) => ref.watch(connectivityServiceProvider).onStatusChanged,
);

final appInfoServiceProvider = Provider<AppInfoService>((ref) => AppInfoService());

// ── Networking ─────────────────────────────────────────────────────────────

final dioProvider = Provider<Dio>((ref) {
  return createDio(
    config: ref.watch(appConfigProvider),
    storage: ref.watch(secureStorageProvider),
    connectivity: ref.watch(connectivityServiceProvider),
    logger: ref.watch(appLoggerProvider),
  );
});

final apiClientProvider = Provider<ApiClient>((ref) => ApiClient(ref.watch(dioProvider)));

// ── Firebase (overridden in bootstrap; defaults to no-op) ──────────────────

final analyticsServiceProvider = Provider<AnalyticsService>(
  (ref) => NoopAnalyticsService(ref.watch(appLoggerProvider)),
);

// ── UI state controllers ───────────────────────────────────────────────────

final themeModeControllerProvider = StateNotifierProvider<ThemeModeController, ThemeMode>(
  (ref) => ThemeModeController(ref.watch(preferenceManagerProvider)),
);

final localeControllerProvider = StateNotifierProvider<LocaleController, Locale>(
  (ref) => LocaleController(ref.watch(preferenceManagerProvider)),
);

/// App-wide constant values (non-visual). Design tokens live under `core/theme`.
abstract final class AppConstants {
  const AppConstants._();

  static const String appName = 'CashNest';

  /// Default request page size for paginated endpoints.
  static const int defaultPageSize = 20;

  /// Hive box names.
  static const String cacheBoxName = 'cashnest_cache';
  static const String prefsBoxName = 'cashnest_prefs';

  /// Animation durations.
  static const Duration shortAnimation = Duration(milliseconds: 150);
  static const Duration mediumAnimation = Duration(milliseconds: 300);
}

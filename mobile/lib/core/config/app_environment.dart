/// Build-time environment selector for the app.
///
/// The active flavor is chosen via `--dart-define=APP_ENV=<name>` so the same
/// binary can target development, staging, or production without code changes.
enum AppEnvironment {
  development,
  staging,
  production;

  /// Resolve the active environment from the compile-time define
  /// (defaults to [AppEnvironment.development]).
  static AppEnvironment resolve() {
    const raw = String.fromEnvironment('APP_ENV', defaultValue: 'development');
    return AppEnvironment.values.firstWhere(
      (env) => env.name == raw,
      orElse: () => AppEnvironment.development,
    );
  }

  bool get isProduction => this == AppEnvironment.production;

  bool get isDevelopment => this == AppEnvironment.development;
}

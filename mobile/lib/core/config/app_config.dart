import 'app_environment.dart';

/// Immutable, environment-aware application configuration.
///
/// Values come from `--dart-define`s with sensible per-flavor defaults so the
/// foundation runs out of the box in development. A plain immutable class is used
/// here (rather than a generated model) to keep the foundation buildable before
/// code generation runs; feature DTOs use Freezed/JsonSerializable.
class AppConfig {
  const AppConfig({
    required this.environment,
    required this.apiBaseUrl,
    required this.connectTimeout,
    required this.receiveTimeout,
    required this.enableNetworkLogging,
  });

  final AppEnvironment environment;
  final String apiBaseUrl;
  final Duration connectTimeout;
  final Duration receiveTimeout;
  final bool enableNetworkLogging;

  /// Build the configuration for the resolved environment.
  factory AppConfig.resolve() {
    final env = AppEnvironment.resolve();

    const defineBaseUrl = String.fromEnvironment('API_BASE_URL');
    final baseUrl = defineBaseUrl.isNotEmpty ? defineBaseUrl : _defaultBaseUrl(env);

    return AppConfig(
      environment: env,
      apiBaseUrl: baseUrl,
      connectTimeout: const Duration(seconds: 15),
      receiveTimeout: const Duration(seconds: 20),
      enableNetworkLogging: !env.isProduction,
    );
  }

  static String _defaultBaseUrl(AppEnvironment env) {
    return switch (env) {
      AppEnvironment.production => 'https://api.cashnest.app/v1',
      AppEnvironment.staging => 'https://staging-api.cashnest.app/v1',
      AppEnvironment.development => 'https://dev-api.cashnest.app/v1',
    };
  }

  AppConfig copyWith({
    AppEnvironment? environment,
    String? apiBaseUrl,
    Duration? connectTimeout,
    Duration? receiveTimeout,
    bool? enableNetworkLogging,
  }) {
    return AppConfig(
      environment: environment ?? this.environment,
      apiBaseUrl: apiBaseUrl ?? this.apiBaseUrl,
      connectTimeout: connectTimeout ?? this.connectTimeout,
      receiveTimeout: receiveTimeout ?? this.receiveTimeout,
      enableNetworkLogging: enableNetworkLogging ?? this.enableNetworkLogging,
    );
  }
}

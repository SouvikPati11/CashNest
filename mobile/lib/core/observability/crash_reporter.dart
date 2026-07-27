import '../logging/app_logger.dart';

/// Crash-reporting integration hook.
///
/// A thin abstraction so the app never depends on a concrete crash service.
/// [NoopCrashReporter] is used until a provider (e.g. Firebase Crashlytics) is
/// wired in a deployment that ships the native config. To enable Crashlytics:
/// add `firebase_crashlytics`, implement [CrashReporter] over
/// `FirebaseCrashlytics.instance`, and override [crashReporterProvider] in
/// `bootstrap`.
abstract interface class CrashReporter {
  /// Record a non-fatal (caught) error.
  Future<void> recordError(Object error, StackTrace? stack, {bool fatal = false});

  /// Record a Flutter framework error.
  Future<void> recordFlutterError(Object error, StackTrace? stack);

  /// Attach the signed-in user id to subsequent reports (null on logout).
  Future<void> setUserId(String? id);

  /// Breadcrumb log line attached to the next crash.
  Future<void> log(String message);

  /// Whether collection is enabled (disabled in debug by default).
  Future<void> setEnabled(bool enabled);
}

/// Default reporter: forwards to the app logger. Real crash aggregation is
/// provided by a deployment-specific implementation.
class LoggingCrashReporter implements CrashReporter {
  LoggingCrashReporter(this._logger);

  final AppLogger _logger;

  @override
  Future<void> recordError(Object error, StackTrace? stack, {bool fatal = false}) async {
    _logger.error('crash(${fatal ? 'fatal' : 'non-fatal'}): $error', error, stack);
  }

  @override
  Future<void> recordFlutterError(Object error, StackTrace? stack) async {
    _logger.error('crash(flutter): $error', error, stack);
  }

  @override
  Future<void> setUserId(String? id) async {}

  @override
  Future<void> log(String message) async {
    _logger.debug('crash-breadcrumb: $message');
  }

  @override
  Future<void> setEnabled(bool enabled) async {}
}

import 'dart:async';

import 'package:flutter/foundation.dart';

import '../logging/app_logger.dart';
import '../observability/crash_reporter.dart';

/// Installs process-wide error hooks so no failure goes unlogged.
///
/// Wired from [runGuarded] in bootstrap: captures Flutter framework errors,
/// platform (engine) errors, and uncaught async errors from the guarded zone,
/// logging them and forwarding to the [CrashReporter] when one is provided
/// (crash-reporting hook — task 12).
class GlobalErrorHandler {
  GlobalErrorHandler(this._logger, {CrashReporter? crashReporter})
      : _crashReporter = crashReporter;

  final AppLogger _logger;
  final CrashReporter? _crashReporter;

  void install() {
    FlutterError.onError = (FlutterErrorDetails details) {
      _logger.error(
        'FlutterError: ${details.exceptionAsString()}',
        details.exception,
        details.stack,
      );
      _crashReporter?.recordFlutterError(details.exception, details.stack);
    };

    PlatformDispatcher.instance.onError = (Object error, StackTrace stack) {
      _logger.error('Uncaught platform error', error, stack);
      _crashReporter?.recordError(error, stack, fatal: true);
      return true;
    };
  }

  void reportZoneError(Object error, StackTrace stack) {
    _logger.error('Uncaught zone error', error, stack);
    _crashReporter?.recordError(error, stack, fatal: true);
  }
}

/// Run [body] inside a guarded zone with the global error handler installed.
Future<void> runGuarded(
  AppLogger logger,
  Future<void> Function() body, {
  CrashReporter? crashReporter,
}) async {
  final handler = GlobalErrorHandler(logger, crashReporter: crashReporter)..install();

  await runZonedGuarded<Future<void>>(
    body,
    handler.reportZoneError,
  );
}

import 'dart:async';

import 'package:flutter/foundation.dart';

import '../logging/app_logger.dart';

/// Installs process-wide error hooks so no failure goes unlogged.
///
/// Wired from [runGuarded] in bootstrap: captures Flutter framework errors,
/// platform (engine) errors, and uncaught async errors from the guarded zone.
class GlobalErrorHandler {
  GlobalErrorHandler(this._logger);

  final AppLogger _logger;

  void install() {
    FlutterError.onError = (FlutterErrorDetails details) {
      _logger.error(
        'FlutterError: ${details.exceptionAsString()}',
        details.exception,
        details.stack,
      );
    };

    PlatformDispatcher.instance.onError = (Object error, StackTrace stack) {
      _logger.error('Uncaught platform error', error, stack);
      return true;
    };
  }

  void reportZoneError(Object error, StackTrace stack) {
    _logger.error('Uncaught zone error', error, stack);
  }
}

/// Run [body] inside a guarded zone with the global error handler installed.
Future<void> runGuarded(
  AppLogger logger,
  Future<void> Function() body,
) async {
  final handler = GlobalErrorHandler(logger)..install();

  await runZonedGuarded<Future<void>>(
    body,
    handler.reportZoneError,
  );
}

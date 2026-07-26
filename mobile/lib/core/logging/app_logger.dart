import 'package:logger/logger.dart';

/// Thin wrapper over the `logger` package giving the app a single, swappable
/// logging surface. In release builds the level is raised so debug noise is
/// suppressed.
class AppLogger {
  AppLogger({bool verbose = false})
      : _logger = Logger(
          level: verbose ? Level.debug : Level.warning,
          printer: PrettyPrinter(
            methodCount: 0,
            errorMethodCount: 6,
            lineLength: 100,
            colors: false,
            printEmojis: false,
          ),
        );

  final Logger _logger;

  void debug(String message, [Object? error, StackTrace? stackTrace]) =>
      _logger.d(message, error: error, stackTrace: stackTrace);

  void info(String message, [Object? error, StackTrace? stackTrace]) =>
      _logger.i(message, error: error, stackTrace: stackTrace);

  void warn(String message, [Object? error, StackTrace? stackTrace]) =>
      _logger.w(message, error: error, stackTrace: stackTrace);

  void error(String message, [Object? error, StackTrace? stackTrace]) =>
      _logger.e(message, error: error, stackTrace: stackTrace);
}

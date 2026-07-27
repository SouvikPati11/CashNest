import '../logging/app_logger.dart';

/// A performance trace handle. Stop it to record the elapsed duration.
abstract interface class PerfTrace {
  void putAttribute(String name, String value);
  void incrementMetric(String name, int value);
  Future<void> stop();
}

/// Performance-monitoring integration hook.
///
/// Abstraction so the app never depends on a concrete APM. [NoopPerformanceMonitor]
/// is the default; a deployment can wire Firebase Performance by adding
/// `firebase_performance`, implementing this over `FirebasePerformance.instance`,
/// and overriding [performanceMonitorProvider] in `bootstrap`.
abstract interface class PerformanceMonitor {
  /// Start a named custom trace. Always returns a usable handle.
  PerfTrace startTrace(String name);

  Future<void> setEnabled(bool enabled);
}

/// A no-op trace that measures wall-clock time and logs it at debug level.
class _LoggingTrace implements PerfTrace {
  _LoggingTrace(this._name, this._logger) : _stopwatch = Stopwatch()..start();

  final String _name;
  final AppLogger _logger;
  final Stopwatch _stopwatch;

  @override
  void putAttribute(String name, String value) {}

  @override
  void incrementMetric(String name, int value) {}

  @override
  Future<void> stop() async {
    _stopwatch.stop();
    _logger.debug('perf: $_name took ${_stopwatch.elapsedMilliseconds}ms');
  }
}

/// Default monitor: measures + logs traces without an external backend.
class NoopPerformanceMonitor implements PerformanceMonitor {
  NoopPerformanceMonitor(this._logger);

  final AppLogger _logger;

  @override
  PerfTrace startTrace(String name) => _LoggingTrace(name, _logger);

  @override
  Future<void> setEnabled(bool enabled) async {}
}

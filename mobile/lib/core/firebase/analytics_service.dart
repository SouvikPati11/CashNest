import 'package:firebase_analytics/firebase_analytics.dart';

import '../logging/app_logger.dart';

/// Analytics abstraction so the app never depends on Firebase directly.
abstract interface class AnalyticsService {
  Future<void> logEvent(String name, {Map<String, Object>? parameters});

  Future<void> setCurrentScreen(String screenName);

  Future<void> setUserId(String? id);
}

/// Firebase-backed analytics (used when Firebase initialized successfully).
class FirebaseAnalyticsService implements AnalyticsService {
  FirebaseAnalyticsService({FirebaseAnalytics? analytics})
      : _analytics = analytics ?? FirebaseAnalytics.instance;

  final FirebaseAnalytics _analytics;

  @override
  Future<void> logEvent(String name, {Map<String, Object>? parameters}) =>
      _analytics.logEvent(name: name, parameters: parameters);

  @override
  Future<void> setCurrentScreen(String screenName) =>
      _analytics.logScreenView(screenName: screenName);

  @override
  Future<void> setUserId(String? id) => _analytics.setUserId(id: id);
}

/// No-op analytics used when Firebase is unavailable; logs at debug level.
class NoopAnalyticsService implements AnalyticsService {
  NoopAnalyticsService(this._logger);

  final AppLogger _logger;

  @override
  Future<void> logEvent(String name, {Map<String, Object>? parameters}) async {
    _logger.debug('analytics(noop) event: $name');
  }

  @override
  Future<void> setCurrentScreen(String screenName) async {}

  @override
  Future<void> setUserId(String? id) async {}
}

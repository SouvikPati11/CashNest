import 'package:flutter/widgets.dart';

import '../firebase/analytics_service.dart';

/// A [NavigatorObserver] that reports screen views to [AnalyticsService].
///
/// Registered on the app router so navigation automatically produces analytics
/// screen-view events (analytics hook — task 13).
class AnalyticsRouteObserver extends NavigatorObserver {
  AnalyticsRouteObserver(this._analytics);

  final AnalyticsService _analytics;

  void _track(Route<dynamic>? route) {
    final name = route?.settings.name;
    if (name != null && name.isNotEmpty) {
      _analytics.setCurrentScreen(name);
    }
  }

  @override
  void didPush(Route<dynamic> route, Route<dynamic>? previousRoute) {
    _track(route);
  }

  @override
  void didReplace({Route<dynamic>? newRoute, Route<dynamic>? oldRoute}) {
    _track(newRoute);
  }

  @override
  void didPop(Route<dynamic> route, Route<dynamic>? previousRoute) {
    _track(previousRoute);
  }
}

import 'dart:async';

import 'package:firebase_messaging/firebase_messaging.dart';

import '../deeplink/deep_link_resolver.dart';
import '../logging/app_logger.dart';
import 'push_messaging_service.dart';

/// Wires FCM message handling across all app states into app navigation.
///
///  - **foreground** (`onMessage`): reports to [onForegroundMessage] so the UI
///    can refresh (e.g. the unread badge). No system notification is auto-shown
///    in the foreground.
///  - **background tap** (`onMessageOpenedApp`): resolves the payload's deep
///    link and navigates via [onNavigate].
///  - **terminated tap** (`getInitialMessage`): same, for a cold start.
///
/// Navigation is delegated through callbacks so this manager stays decoupled
/// from the router.
class PushNotificationManager {
  PushNotificationManager({
    required PushMessagingService messaging,
    required AppLogger logger,
    required void Function(String location) onNavigate,
    void Function(RemoteMessage message)? onForegroundMessage,
    void Function(String token)? onToken,
  })  : _messaging = messaging,
        _logger = logger,
        _onNavigate = onNavigate,
        _onForegroundMessage = onForegroundMessage,
        _onToken = onToken;

  final PushMessagingService _messaging;
  final AppLogger _logger;
  final void Function(String location) _onNavigate;
  final void Function(RemoteMessage message)? _onForegroundMessage;
  final void Function(String token)? _onToken;

  final List<StreamSubscription<dynamic>> _subscriptions = [];
  bool _started = false;

  /// Request permission, wire the message streams, and process any launch
  /// message. Safe to call once; repeated calls are ignored.
  Future<void> start() async {
    if (_started) {
      return;
    }
    _started = true;

    await _messaging.requestPermission();

    final token = await _messaging.getToken();
    if (token != null) {
      _onToken?.call(token);
    }
    _subscriptions.add(_messaging.onTokenRefresh.listen((t) => _onToken?.call(t)));

    _subscriptions.add(_messaging.onForegroundMessage.listen((message) {
      _logger.debug('FCM foreground: ${message.messageId}');
      _onForegroundMessage?.call(message);
    }));

    _subscriptions.add(_messaging.onMessageOpenedApp.listen(_handleOpened));

    // Terminated → opened via tap.
    final initial = await _messaging.getInitialMessage();
    if (initial != null) {
      _handleOpened(initial);
    }
  }

  void _handleOpened(RemoteMessage message) {
    final raw = _deepLinkOf(message);
    final location = DeepLinkResolver.resolve(raw) ?? '/notifications';
    _logger.debug('FCM opened → $location');
    _onNavigate(location);
  }

  static String? _deepLinkOf(RemoteMessage message) {
    final data = message.data;
    return (data['deep_link'] ?? data['deeplink'] ?? data['route'] ?? data['link']) as String?;
  }

  Future<void> dispose() async {
    for (final sub in _subscriptions) {
      await sub.cancel();
    }
    _subscriptions.clear();
  }
}

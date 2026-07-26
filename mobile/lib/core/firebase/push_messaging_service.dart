import 'package:firebase_messaging/firebase_messaging.dart';

import '../logging/app_logger.dart';

/// Firebase Cloud Messaging wrapper (foundation-level only).
///
/// Handles permission requests, token retrieval, and exposes the message
/// streams. Rendering notifications and reacting to taps belongs to the (out of
/// scope) notifications feature; this service just provides the plumbing.
class PushMessagingService {
  PushMessagingService(this._logger, {FirebaseMessaging? messaging})
      : _messaging = messaging ?? FirebaseMessaging.instance;

  final FirebaseMessaging _messaging;
  final AppLogger _logger;

  Future<void> requestPermission() async {
    try {
      await _messaging.requestPermission();
    } catch (error, stack) {
      _logger.warn('Push permission request failed.', error, stack);
    }
  }

  Future<String?> getToken() async {
    try {
      return await _messaging.getToken();
    } catch (error, stack) {
      _logger.warn('Unable to fetch FCM token.', error, stack);
      return null;
    }
  }

  Stream<String> get onTokenRefresh => _messaging.onTokenRefresh;

  Stream<RemoteMessage> get onForegroundMessage => FirebaseMessaging.onMessage;

  Stream<RemoteMessage> get onMessageOpenedApp => FirebaseMessaging.onMessageOpenedApp;
}

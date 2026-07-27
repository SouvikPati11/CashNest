import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';

/// Top-level FCM background/terminated message handler.
///
/// Runs in a **separate isolate** with no app state, so it must be a top-level
/// (or static) function annotated with `@pragma('vm:entry-point')`, initialize
/// Firebase itself, and do only lightweight, UI-free work (the OS renders the
/// notification tray entry). Navigation on tap is handled in the main isolate
/// (`onMessageOpenedApp` / `getInitialMessage`).
///
/// Registered in `bootstrap` via
/// `FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler)`.
@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  try {
    await Firebase.initializeApp();
  } catch (_) {
    // Firebase not configured in this build — nothing to do.
    return;
  }
  if (kDebugMode) {
    debugPrint('FCM background message: ${message.messageId}');
  }
  // Intentionally no UI / navigation here (background isolate). Data-only
  // side effects (e.g. cache writes) could go here if needed.
}

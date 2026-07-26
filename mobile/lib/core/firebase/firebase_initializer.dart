import 'package:firebase_core/firebase_core.dart';

import '../logging/app_logger.dart';

/// Guarded Firebase initialization.
///
/// The FlutterFire-generated `firebase_options.dart` and platform config files
/// are provisioned per deployment and are intentionally not committed. When they
/// are absent (e.g. local foundation runs), initialization is skipped gracefully
/// so the app still boots; analytics/push then fall back to no-op behavior.
class FirebaseInitializer {
  FirebaseInitializer(this._logger);

  final AppLogger _logger;

  bool _initialized = false;

  bool get isInitialized => _initialized;

  Future<bool> initialize() async {
    if (_initialized) {
      return true;
    }

    try {
      await Firebase.initializeApp();
      _initialized = true;
      _logger.info('Firebase initialized.');
    } catch (error, stack) {
      _logger.warn('Firebase not configured; continuing without it.', error, stack);
      _initialized = false;
    }

    return _initialized;
  }
}

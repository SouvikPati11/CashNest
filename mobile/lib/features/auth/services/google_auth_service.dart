import 'package:google_sign_in/google_sign_in.dart';

import '../../../core/logging/app_logger.dart';

/// Obtains a Google OIDC ID token for backend exchange (`POST /auth/google`).
abstract interface class GoogleAuthService {
  /// Returns the ID token, or null when the user cancels the flow.
  Future<String?> obtainIdToken();

  Future<void> signOut();
}

/// [GoogleAuthService] backed by the `google_sign_in` plugin.
///
/// Isolates the plugin dependency so the rest of the module depends only on the
/// abstraction (and so it can be faked in tests).
class GoogleAuthServiceImpl implements GoogleAuthService {
  GoogleAuthServiceImpl({GoogleSignIn? googleSignIn, AppLogger? logger})
      : _google = googleSignIn ?? GoogleSignIn(scopes: const ['email']),
        _logger = logger;

  final GoogleSignIn _google;
  final AppLogger? _logger;

  @override
  Future<String?> obtainIdToken() async {
    try {
      final account = await _google.signIn();
      if (account == null) {
        return null; // user cancelled
      }
      final auth = await account.authentication;
      return auth.idToken;
    } catch (error, stack) {
      _logger?.warn('Google sign-in failed.', error, stack);
      return null;
    }
  }

  @override
  Future<void> signOut() async {
    try {
      await _google.signOut();
    } catch (error, stack) {
      _logger?.warn('Google sign-out failed.', error, stack);
    }
  }
}

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/error/app_exception.dart';
import '../../../core/logging/app_logger.dart';
import '../../../core/network/api_result.dart';
import '../data/auth_repository.dart';
import '../models/auth_session.dart';
import '../models/register_result.dart';
import '../models/verify_result.dart';
import '../services/auth_token_store.dart';
import '../services/google_auth_service.dart';
import 'auth_state.dart';

/// Owns global authentication state and orchestrates the auth flows.
///
/// Session-mutating flows persist tokens through [AuthTokenStore] and update the
/// [AuthState] so the router redirects. Each method returns an [ApiResult] so
/// screens can render loading/error without the controller holding form state.
class AuthController extends StateNotifier<AuthState> {
  AuthController({
    required AuthRepository repository,
    required AuthTokenStore tokenStore,
    required GoogleAuthService google,
    AppLogger? logger,
  })  : _repository = repository,
        _tokenStore = tokenStore,
        _google = google,
        _logger = logger,
        super(const AuthState.unknown());

  final AuthRepository _repository;
  final AuthTokenStore _tokenStore;
  final GoogleAuthService _google;
  final AppLogger? _logger;

  /// Restore a persisted session on startup (splash flow).
  Future<void> restoreSession() async {
    final hasSession = await _tokenStore.hasSession();
    state = hasSession ? const AuthState.authenticated(null) : const AuthState.unauthenticated();
  }

  Future<ApiResult<AuthSession>> loginWithEmail({
    required String email,
    required String password,
  }) async {
    final result = await _repository.loginWithEmail(email: email.trim(), password: password);
    return _completeSession(result);
  }

  Future<ApiResult<RegisterResult>> register({
    required String name,
    required String email,
    required String password,
    String? referralCode,
  }) {
    // Registration requires email verification before tokens are issued, so it
    // does not change auth state here — the screen routes to verification.
    return _repository.register(
      name: name.trim(),
      email: email.trim(),
      password: password,
      referralCode: referralCode,
    );
  }

  /// Runs the Google flow; returns null when the user cancels.
  Future<ApiResult<AuthSession>?> signInWithGoogle({String? referralCode}) async {
    final idToken = await _google.obtainIdToken();
    if (idToken == null) {
      return null;
    }
    final result = await _repository.signInWithGoogle(idToken: idToken, referralCode: referralCode);
    return _completeSession(result);
  }

  Future<ApiResult<VerifyResult>> verifyEmail({
    required String email,
    required String otp,
  }) async {
    final result = await _repository.verifyEmail(email: email.trim(), otp: otp.trim());
    if (result is ApiSuccess<VerifyResult> && result.data.verified) {
      await _tokenStore.save(result.data.tokens);
      state = const AuthState.authenticated(null);
    }
    return result;
  }

  Future<ApiResult<bool>> requestPasswordReset({required String email}) {
    return _repository.forgotPassword(email: email.trim());
  }

  /// Log out: best-effort server revoke, then clear local session.
  Future<void> logout() async {
    try {
      final refreshToken = await _tokenStore.readRefreshToken();
      await _repository.logout(refreshToken: refreshToken);
    } on Object catch (error, stack) {
      _logger?.warn('Logout API call failed; clearing local session anyway.', error, stack);
    } finally {
      await _google.signOut();
      await _tokenStore.clear();
      state = const AuthState.unauthenticated();
    }
  }

  Future<ApiResult<AuthSession>> _completeSession(ApiResult<AuthSession> result) async {
    if (result is ApiSuccess<AuthSession>) {
      await _tokenStore.save(result.data.tokens);
      state = AuthState.authenticated(result.data.user);
    } else if (result is ApiFailure<AuthSession> && result.error is UnauthorizedException) {
      state = const AuthState.unauthenticated();
    }
    return result;
  }
}

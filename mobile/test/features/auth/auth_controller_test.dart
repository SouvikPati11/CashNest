import 'package:cashnest/core/error/app_exception.dart';
import 'package:cashnest/core/network/api_result.dart';
import 'package:cashnest/features/auth/application/auth_controller.dart';
import 'package:cashnest/features/auth/application/auth_state.dart';
import 'package:cashnest/features/auth/data/auth_repository.dart';
import 'package:cashnest/features/auth/models/auth_session.dart';
import 'package:cashnest/features/auth/models/auth_tokens.dart';
import 'package:cashnest/features/auth/models/auth_user.dart';
import 'package:cashnest/features/auth/models/register_result.dart';
import 'package:cashnest/features/auth/models/verify_result.dart';
import 'package:cashnest/features/auth/services/auth_token_store.dart';
import 'package:cashnest/features/auth/services/google_auth_service.dart';
import 'package:flutter_test/flutter_test.dart';

const _session = AuthSession(
  user: AuthUser(uuid: 'u_1', name: 'Asha', email: 'asha@x.io'),
  tokens: AuthTokens(accessToken: 'at', refreshToken: 'rt', expiresIn: 1800),
);

class _FakeRepo implements AuthRepository {
  ApiResult<AuthSession> loginResult = const ApiResult.success(_session);
  ApiResult<AuthSession> googleResult = const ApiResult.success(_session);
  ApiResult<RegisterResult> registerResult = const ApiResult.success(
    RegisterResult(
      user: AuthUser(uuid: 'u_1', name: 'Asha', email: 'asha@x.io'),
      verificationRequired: true,
    ),
  );
  ApiResult<VerifyResult> verifyResult = const ApiResult.success(
    VerifyResult(verified: true, tokens: AuthTokens(accessToken: 'at', refreshToken: 'rt')),
  );
  bool logoutCalled = false;

  @override
  Future<ApiResult<AuthSession>> loginWithEmail({required String email, required String password}) async =>
      loginResult;

  @override
  Future<ApiResult<AuthSession>> signInWithGoogle({required String idToken, String? referralCode}) async =>
      googleResult;

  @override
  Future<ApiResult<RegisterResult>> register({
    required String name,
    required String email,
    required String password,
    String? referralCode,
  }) async =>
      registerResult;

  @override
  Future<ApiResult<VerifyResult>> verifyEmail({required String email, required String otp}) async =>
      verifyResult;

  @override
  Future<ApiResult<bool>> forgotPassword({required String email}) async => const ApiResult.success(true);

  @override
  Future<ApiResult<bool>> logout({String? refreshToken}) async {
    logoutCalled = true;
    return const ApiResult.success(true);
  }
}

class _MemStore implements AuthTokenStore {
  AuthTokens? saved;
  String? access;
  String? refresh;

  @override
  Future<void> save(AuthTokens tokens) async {
    saved = tokens;
    access = tokens.accessToken;
    refresh = tokens.refreshToken;
  }

  @override
  Future<String?> readAccessToken() async => access;

  @override
  Future<String?> readRefreshToken() async => refresh;

  @override
  Future<bool> hasSession() async => access != null && access!.isNotEmpty;

  @override
  Future<void> clear() async {
    saved = null;
    access = null;
    refresh = null;
  }
}

class _FakeGoogle implements GoogleAuthService {
  String? idToken = 'google-id-token';
  bool signedOut = false;

  @override
  Future<String?> obtainIdToken() async => idToken;

  @override
  Future<void> signOut() async => signedOut = true;
}

void main() {
  late _FakeRepo repo;
  late _MemStore store;
  late _FakeGoogle google;

  AuthController build() => AuthController(repository: repo, tokenStore: store, google: google);

  setUp(() {
    repo = _FakeRepo();
    store = _MemStore();
    google = _FakeGoogle();
  });

  group('restoreSession', () {
    test('no token -> unauthenticated', () async {
      final controller = build();
      await controller.restoreSession();
      expect(controller.debugState.status, AuthStatus.unauthenticated);
    });

    test('token present -> authenticated', () async {
      store.access = 'at';
      final controller = build();
      await controller.restoreSession();
      expect(controller.debugState.status, AuthStatus.authenticated);
    });
  });

  test('loginWithEmail success authenticates and persists tokens', () async {
    final controller = build();
    final result = await controller.loginWithEmail(email: 'asha@x.io', password: 'secret1pass');

    expect(result.isSuccess, isTrue);
    expect(controller.debugState.status, AuthStatus.authenticated);
    expect(controller.debugState.user?.uuid, 'u_1');
    expect(store.saved?.accessToken, 'at');
  });

  test('loginWithEmail unauthorized failure marks unauthenticated', () async {
    repo.loginResult = const ApiResult.failure(UnauthorizedException('bad'));
    final controller = build();

    final result = await controller.loginWithEmail(email: 'a@x.io', password: 'x');

    expect(result.isFailure, isTrue);
    expect(controller.debugState.status, AuthStatus.unauthenticated);
    expect(store.saved, isNull);
  });

  test('register does not change auth state', () async {
    final controller = build();
    final result = await controller.register(name: 'Asha', email: 'a@x.io', password: 'secret1pass');

    expect(result.isSuccess, isTrue);
    expect(controller.debugState.status, AuthStatus.unknown);
  });

  test('verifyEmail success authenticates and stores tokens', () async {
    final controller = build();
    final result = await controller.verifyEmail(email: 'a@x.io', otp: '123456');

    expect(result.isSuccess, isTrue);
    expect(controller.debugState.status, AuthStatus.authenticated);
    expect(store.saved?.accessToken, 'at');
  });

  test('signInWithGoogle cancelled returns null and keeps state', () async {
    google.idToken = null;
    final controller = build();

    final result = await controller.signInWithGoogle();

    expect(result, isNull);
    expect(controller.debugState.status, AuthStatus.unknown);
  });

  test('signInWithGoogle success authenticates', () async {
    final controller = build();
    final result = await controller.signInWithGoogle();

    expect(result, isNotNull);
    expect(controller.debugState.status, AuthStatus.authenticated);
  });

  test('logout revokes, clears tokens, and sets unauthenticated', () async {
    store.access = 'at';
    store.refresh = 'rt';
    final controller = build();

    await controller.logout();

    expect(repo.logoutCalled, isTrue);
    expect(google.signedOut, isTrue);
    expect(store.access, isNull);
    expect(controller.debugState.status, AuthStatus.unauthenticated);
  });
}

import 'package:dio/dio.dart';

import '../../../core/network/api_client.dart';
import '../../../core/network/api_result.dart';
import '../../../core/network/interceptors/jwt_interceptor.dart';
import '../models/auth_session.dart';
import '../models/register_result.dart';
import '../models/verify_result.dart';
import 'auth_repository.dart';

/// [AuthRepository] implementation over the foundation [ApiClient].
///
/// Auth endpoints are public, so requests opt out of the JWT interceptor via
/// the `requiresAuth = false` request extra.
class AuthRepositoryImpl implements AuthRepository {
  AuthRepositoryImpl(this._client);

  final ApiClient _client;

  static final Options _publicOptions = Options(
    extra: {JwtInterceptor.requiresAuthKey: false},
  );

  static Map<String, dynamic> _asMap(dynamic data) =>
      data is Map<String, dynamic> ? data : const <String, dynamic>{};

  @override
  Future<ApiResult<AuthSession>> loginWithEmail({
    required String email,
    required String password,
  }) {
    return _client.post<AuthSession>(
      '/auth/email/login',
      body: {'email': email, 'password': password},
      options: _publicOptions,
      decoder: (data) => AuthSession.fromJson(_asMap(data)),
    );
  }

  @override
  Future<ApiResult<RegisterResult>> register({
    required String name,
    required String email,
    required String password,
    String? referralCode,
  }) {
    return _client.post<RegisterResult>(
      '/auth/email/register',
      body: {
        'name': name,
        'email': email,
        'password': password,
        if (referralCode != null && referralCode.isNotEmpty) 'referral_code': referralCode,
      },
      options: _publicOptions,
      decoder: (data) => RegisterResult.fromJson(_asMap(data)),
    );
  }

  @override
  Future<ApiResult<AuthSession>> signInWithGoogle({
    required String idToken,
    String? referralCode,
  }) {
    return _client.post<AuthSession>(
      '/auth/google',
      body: {
        'id_token': idToken,
        if (referralCode != null && referralCode.isNotEmpty) 'referral_code': referralCode,
      },
      options: _publicOptions,
      decoder: (data) => AuthSession.fromJson(_asMap(data)),
    );
  }

  @override
  Future<ApiResult<VerifyResult>> verifyEmail({
    required String email,
    required String otp,
  }) {
    return _client.post<VerifyResult>(
      '/auth/email/verify',
      body: {'email': email, 'otp': otp},
      options: _publicOptions,
      decoder: (data) => VerifyResult.fromJson(_asMap(data)),
    );
  }

  @override
  Future<ApiResult<bool>> forgotPassword({required String email}) {
    return _client.post<bool>(
      '/auth/password/forgot',
      body: {'email': email},
      options: _publicOptions,
      decoder: (data) => _asMap(data)['sent'] == true,
    );
  }

  @override
  Future<ApiResult<bool>> logout({String? refreshToken}) {
    return _client.post<bool>(
      '/auth/logout',
      body: {
        if (refreshToken != null && refreshToken.isNotEmpty) 'refresh_token': refreshToken,
      },
      decoder: (data) => _asMap(data)['logged_out'] == true,
    );
  }
}

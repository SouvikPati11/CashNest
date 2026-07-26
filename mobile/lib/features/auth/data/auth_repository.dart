import '../../../core/network/api_result.dart';
import '../models/auth_session.dart';
import '../models/register_result.dart';
import '../models/verify_result.dart';

/// Contract for authentication API operations. Returns [ApiResult] so callers
/// handle success/failure explicitly.
abstract interface class AuthRepository {
  Future<ApiResult<AuthSession>> loginWithEmail({
    required String email,
    required String password,
  });

  Future<ApiResult<RegisterResult>> register({
    required String name,
    required String email,
    required String password,
    String? referralCode,
  });

  Future<ApiResult<AuthSession>> signInWithGoogle({
    required String idToken,
    String? referralCode,
  });

  Future<ApiResult<VerifyResult>> verifyEmail({
    required String email,
    required String otp,
  });

  Future<ApiResult<bool>> forgotPassword({required String email});

  Future<ApiResult<bool>> logout({String? refreshToken});
}

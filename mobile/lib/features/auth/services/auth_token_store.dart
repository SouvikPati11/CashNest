import '../../../core/storage/secure_storage_service.dart';
import '../models/auth_tokens.dart';

/// Secure persistence of the auth token pair.
abstract interface class AuthTokenStore {
  Future<void> save(AuthTokens tokens);

  Future<String?> readAccessToken();

  Future<String?> readRefreshToken();

  Future<bool> hasSession();

  Future<void> clear();
}

/// [AuthTokenStore] backed by the foundation's [SecureStorageService]
/// (platform keystore/keychain).
class SecureAuthTokenStore implements AuthTokenStore {
  SecureAuthTokenStore(this._storage);

  final SecureStorageService _storage;

  @override
  Future<void> save(AuthTokens tokens) => _storage.saveTokens(
        accessToken: tokens.accessToken,
        refreshToken: tokens.refreshToken,
      );

  @override
  Future<String?> readAccessToken() => _storage.readAccessToken();

  @override
  Future<String?> readRefreshToken() => _storage.readRefreshToken();

  @override
  Future<bool> hasSession() async {
    final token = await _storage.readAccessToken();
    return token != null && token.isNotEmpty;
  }

  @override
  Future<void> clear() => _storage.clearTokens();
}

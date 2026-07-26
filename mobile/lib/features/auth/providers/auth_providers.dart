import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../app/di/providers.dart';
import '../application/auth_controller.dart';
import '../application/auth_state.dart';
import '../data/auth_repository.dart';
import '../data/auth_repository_impl.dart';
import '../services/auth_token_store.dart';
import '../services/google_auth_service.dart';

/// Riverpod wiring for the authentication module. Depends on foundation
/// providers (API client, secure storage, logger) without modifying them.

final authTokenStoreProvider = Provider<AuthTokenStore>(
  (ref) => SecureAuthTokenStore(ref.watch(secureStorageProvider)),
);

final googleAuthServiceProvider = Provider<GoogleAuthService>(
  (ref) => GoogleAuthServiceImpl(logger: ref.watch(appLoggerProvider)),
);

final authRepositoryProvider = Provider<AuthRepository>(
  (ref) => AuthRepositoryImpl(ref.watch(apiClientProvider)),
);

final authControllerProvider = StateNotifierProvider<AuthController, AuthState>(
  (ref) => AuthController(
    repository: ref.watch(authRepositoryProvider),
    tokenStore: ref.watch(authTokenStoreProvider),
    google: ref.watch(googleAuthServiceProvider),
    logger: ref.watch(appLoggerProvider),
  ),
);

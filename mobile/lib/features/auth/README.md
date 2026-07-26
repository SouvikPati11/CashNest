# Auth Feature Module

Complete authentication for CashNest, built entirely on the existing foundation
(no foundation files are modified).

## Flows

- **Splash → session restore** (`AuthSplashScreen` + `AuthController.restoreSession`)
- **Authentication guard** (`authRedirect` in `routing/auth_guard.dart`)
- **Email login / register / email verification (OTP)**
- **Google Sign-In** (`GoogleAuthService` → `POST /auth/google`)
- **Forgot password (placeholder)** — sends the enumeration-safe reset request
- **Logout** — server revoke + secure token clear
- **Secure token storage** via the foundation `SecureStorageService`

## Layers

```
models/         AuthUser, AuthTokens, AuthSession, RegisterResult, VerifyResult
data/           AuthRepository (+ Dio-backed impl over foundation ApiClient)
services/       AuthTokenStore (secure), GoogleAuthService
application/    AuthState, AuthController (Riverpod StateNotifier)
providers/      Riverpod wiring (reuses foundation providers)
routing/        AuthRoutes, authRedirect guard, authRouterProvider
presentation/   splash + login/register/verify/forgot screens + widgets
validation/     pure form validators
l10n/           feature-scoped English + Bangla strings
```

## Integration (one line, done at app-wiring time)

The foundation router is left untouched. To activate auth, override the
foundation router with the auth-aware one in the root `ProviderScope`:

```dart
ProviderScope(
  overrides: [
    goRouterProvider.overrideWith((ref) => ref.watch(authRouterProvider)),
    // ...existing bootstrap overrides
  ],
  child: const CashNestApp(),
)
```

`google_sign_in` was added to `pubspec.yaml` as the only dependency required to
implement native Google Sign-In; it is isolated behind `GoogleAuthService`.

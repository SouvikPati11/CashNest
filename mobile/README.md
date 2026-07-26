# CashNest — Flutter App (Foundation)

Production-ready **foundation** for the CashNest mobile app. This layer contains
no business features — only the architecture, theming, navigation, networking,
storage, localization, and shared UI that feature modules build on.

## Stack

- Flutter 3.35+ · Dart 3 · Material 3
- **Riverpod** (state + DI) · **GoRouter** (navigation) · **Dio** (HTTP)
- **Freezed** / **json_serializable** (feature model codegen)
- **Hive** + **flutter_secure_storage** (storage)
- **logger**, **connectivity_plus**, **package_info_plus**, **device_info_plus**
- **firebase_core / messaging / analytics**

## Architecture (feature-first)

```
lib/
  app/            # bootstrap, root widget, DI provider registration
  core/           # config, theme, router, network, storage, error,
                  # connectivity, localization, platform, firebase, responsive
  shared/         # reusable widgets + context extensions
  features/       # splash, error, home placeholder (feature modules go here)
```

## Getting started

```bash
cd mobile
flutter pub get

# Generate platform folders (android/ios/web) on first setup:
flutter create .

# Run code generation for feature models that use Freezed / json_serializable:
dart run build_runner build --delete-conflicting-outputs

# Run
flutter run --dart-define=APP_ENV=development
```

### Environments

Configuration is resolved from `--dart-define`s (`APP_ENV`, `API_BASE_URL`) with
per-flavor defaults, so the same binary targets `development`, `staging`, or
`production`.

### Firebase

`firebase_options.dart` and the platform config files are provisioned per
deployment (via `flutterfire configure`) and are git-ignored. When absent the
app boots normally with analytics/push disabled.

## Conventions

- **Models/DTOs** (added by features) use Freezed + json_serializable; run
  `build_runner` after adding them. Foundation code uses plain immutable classes
  so the tree is analyzable before code generation.
- **Networking** goes through `ApiClient` → `ApiResult<T>` (never Dio directly).
- **Errors** are normalized to the `AppException` hierarchy and localized via
  `ErrorMapper`.
- **Secrets** (tokens) live in `SecureStorageService`; preferences in
  `PreferenceManager`; cached documents in `HiveCacheService`.

## Quality

```bash
flutter analyze
flutter test
```

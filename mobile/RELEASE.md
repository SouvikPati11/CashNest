# CashNest — Release & Production Guide

This document covers turning the Flutter app into signed, store-ready builds.
The app code, routing, and feature modules are complete; this guide covers the
production configuration added in the release-preparation phase.

> **Prerequisite — generate platform wrappers.** The repo ships the *production
> overlay* config (manifests, Gradle, plists, ProGuard, icons/splash config,
> web SW). The Flutter-generated wrappers (Gradle wrapper, Xcode project,
> `web/` bootstrap) are not committed. On a fresh checkout run once:
>
> ```bash
> flutter create --platforms=android,ios,web --org com.cashnest .
> flutter pub get
> ```
>
> This regenerates the wrappers **without** overwriting the committed config
> files. Review the diff and keep the committed versions where they differ.

## 1. Environments & flavors (tasks 10, 11)

The environment is chosen at build time via `--dart-define=APP_ENV=<env>` and
mirrored by the Android product flavors (`dev`, `staging`, `production`):

| Flavor      | `APP_ENV`     | Default API base URL                     |
|-------------|---------------|------------------------------------------|
| dev         | development   | https://dev-api.cashnest.app/v1          |
| staging     | staging       | https://staging-api.cashnest.app/v1      |
| production  | production    | https://api.cashnest.app/v1              |

Override the base URL with `--dart-define=API_BASE_URL=...`. See
`lib/core/config/app_config.dart` / `app_environment.dart`.

Convenience scripts: `scripts/run_dev.sh`, `scripts/build_android.sh [flavor]`,
`scripts/build_ios.sh [flavor]`.

## 2. Firebase (tasks 1, 2, 12, 13, 16)

Firebase init is **guarded** (`FirebaseInitializer`): the app boots even without
config, falling back to no-op analytics/push.

1. Create the Firebase project + one app per platform (bundle id `com.cashnest.app`).
2. Download and place (all git-ignored):
   - Android → `android/app/google-services.json`
   - iOS → `ios/Runner/GoogleService-Info.plist`
   - Web → fill `web/firebase-messaging-sw.js` config + generate
     `lib/firebase_options.dart` (`flutterfire configure`); see
     `lib/firebase_options.dart.example`.
3. FCM is wired end-to-end in Dart:
   - **background/terminated:** `firebaseMessagingBackgroundHandler`
     (`core/firebase/messaging_background_handler.dart`), registered in
     `bootstrap`.
   - **foreground / tap / cold-start tap:** `PushNotificationManager`
     (`core/firebase/push_notification_manager.dart`), started by
     `AppLifecycleReactor`; taps resolve a deep link and navigate.
   - Analytics screen views: `AnalyticsRouteObserver` on the router.
   - Crash hooks: `GlobalErrorHandler` → `CrashReporter`
     (`core/observability/crash_reporter.dart`). To enable Crashlytics, add
     `firebase_crashlytics`, implement `CrashReporter`, and override
     `crashReporterProvider` in `bootstrap`.
   - Performance hooks: `performanceMonitorProvider`
     (`core/observability/performance_monitor.dart`).

## 3. App icon & native splash (tasks 3, 4)

Add the source PNGs under `assets/branding/` (see its README), then:

```bash
dart run flutter_launcher_icons
dart run flutter_native_splash:create
```

Android splash theme/resources are in `android/app/src/main/res/`
(`LaunchTheme`, `launch_background.xml`, `colors.xml`).

## 4. Android signing & release (tasks 5, 7, 8, 9)

1. Create an upload keystore and `android/key.properties` from
   `android/key.properties.example` (both git-ignored). Without it, release
   builds fall back to debug signing so the project stays buildable.
2. Release builds enable **R8 full mode + resource shrinking**
   (`android/app/build.gradle`, `android/gradle.properties`) with keeps in
   `android/app/proguard-rules.pro`.
3. Permissions, FCM channel, and deep-link intent-filters are in
   `android/app/src/main/AndroidManifest.xml`.

```bash
scripts/build_android.sh production
```

## 5. iOS release (tasks 6, 7, 9)

- Permissions, background modes (`remote-notification`), and the `cashnest://`
  URL scheme are in `ios/Runner/Info.plist`.
- Set the signing team/bundle id in Xcode; add per-flavor schemes
  (`dev`/`staging`/`production`) with matching xcconfigs. Add the
  associated-domains entitlement for Universal Links.

```bash
scripts/build_ios.sh production
```

## 6. Deep links (task 17)

`DeepLinkResolver` (`core/deeplink/`) maps custom-scheme / app-link / bare-key
payloads to router locations; FCM taps and app links both flow through it into
GoRouter. Android intent-filters + iOS URL types are configured above.

## 7. Startup, offline, lifecycle & logging (tasks 14, 15, 18, 20, 21)

- **Startup (24):** `main → bootstrap → runGuarded(→ crash hooks) → Hive →
  Firebase (guarded) → runApp`. The router splash restores the session, then the
  auth guard routes to `/home` or `/login`.
- **Offline (20):** no blocking network call at startup; Firebase init is
  guarded; the session restores from secure storage; API failures surface as
  error/offline states (global `OfflineBanner`).
- **Secure storage (14):** `flutter_secure_storage` with Android
  EncryptedSharedPreferences.
- **Lifecycle (18):** `AppLifecycleReactor` refreshes the unread badge and logs
  an analytics event on resume.
- **Release logging (21):** `AppLogger` raises the level to `warning` in
  non-verbose (production) builds; network logging is off in production.

## 8. Verify

```bash
flutter analyze
flutter test
scripts/build_android.sh production   # or build_ios.sh
```

# CashNest — CI/CD Guide

This repository builds, tests, and releases the CashNest Flutter app entirely on
GitHub Actions — no local machine required. This guide explains the pipeline, the
secrets it needs, and how to trigger builds, download artifacts, and publish a
release.

> The Flutter app lives in the [`mobile/`](../mobile) subdirectory, so every
> Flutter/Gradle step runs with `working-directory: mobile`.

## 1. Workflows at a glance

| Workflow | File | Trigger | What it does |
|----------|------|---------|--------------|
| **CI**      | [`.github/workflows/ci.yml`](../.github/workflows/ci.yml)        | Push / PR to `main`,`master`,`claude/**` (paths `mobile/**`, `.github/**`) and manual `workflow_dispatch` | Analyze → Test → Build APK+AAB for every flavor, upload artifacts |
| **Release** | [`.github/workflows/release.yml`](../.github/workflows/release.yml) | Publishing a GitHub Release **or** pushing a `v*` tag | Build the signed **production** APK + AAB and attach them to the Release |

Both workflows share two composite actions so setup logic lives in one place:

- [`.github/actions/flutter-setup`](../.github/actions/flutter-setup/action.yml) —
  installs **Java 17 (Temurin)** and **Flutter (stable)** with caching, then runs
  `flutter pub get`. Flutter's build tooling provisions the **Android SDK**
  automatically on first use.
- [`.github/actions/android-prepare`](../.github/actions/android-prepare/action.yml) —
  regenerates the Gradle wrapper (`flutter create`), writes a placeholder
  `google-services.json` (the real one is a secret), and configures release
  signing from the keystore secrets.

### Jobs in the CI workflow

1. **analyze** — `dart format --set-exit-if-changed` + `flutter analyze`.
2. **test** — `flutter test --coverage`; uploads `coverage/lcov.info`.
3. **build** — matrix over the three flavors, runs after analyze **and** test pass:

   | Flavor       | `--dart-define=APP_ENV` | Artifacts |
   |--------------|-------------------------|-----------|
   | `dev`        | `development`           | `cashnest-dev-apk`, `cashnest-dev-aab` |
   | `staging`    | `staging`               | `cashnest-staging-apk`, `cashnest-staging-aab` |
   | `production` | `production`            | `cashnest-production-apk`, `cashnest-production-aab` |

## 2. Caching

- **Pub cache** — handled by `subosito/flutter-action@v2` (`cache: true`), keyed on
  the Flutter version and `pubspec.lock`.
- **Gradle cache** — `actions/cache@v4` over `~/.gradle/caches` and
  `~/.gradle/wrapper`, keyed on `mobile/android/**/*.gradle` + `mobile/pubspec.yaml`.

## 3. Android signing via GitHub Secrets

Release builds are signed from secrets — **the keystore is never committed**. When
the secrets are absent (e.g. a fork PR) the build falls back to debug signing so CI
still stays green.

Create these under **Settings → Secrets and variables → Actions → New repository
secret**:

| Secret | Description |
|--------|-------------|
| `ANDROID_KEYSTORE_BASE64` | Base64 of your `upload-keystore.jks` |
| `ANDROID_KEY_ALIAS`       | Key alias inside the keystore |
| `ANDROID_KEY_PASSWORD`    | Key password |
| `ANDROID_STORE_PASSWORD`  | Keystore password |

### Generate the keystore and the base64 secret

```bash
# 1. Create an upload keystore (once)
keytool -genkey -v -keystore upload-keystore.jks \
  -keyalg RSA -keysize 2048 -validity 10000 -alias upload

# 2. Base64-encode it for the ANDROID_KEYSTORE_BASE64 secret
#    Linux:
base64 -w0 upload-keystore.jks > keystore.base64.txt
#    macOS:
base64 -i upload-keystore.jks -o keystore.base64.txt
```

Paste the contents of `keystore.base64.txt` into `ANDROID_KEYSTORE_BASE64`, and set
`ANDROID_KEY_ALIAS` / `ANDROID_KEY_PASSWORD` / `ANDROID_STORE_PASSWORD` to the values
you chose above. The `android-prepare` action decodes the keystore to
`android/app/upload-keystore.jks` and writes `android/key.properties` at build time.

> The real `google-services.json` / `GoogleService-Info.plist` remain git-ignored.
> CI writes a harmless placeholder so the `google-services` Gradle plugin resolves;
> Firebase init is guarded at runtime, so placeholder config just disables Firebase
> in CI artifacts.

## 4. Triggering a build

- **Automatically** — push to `main`/`master`/`claude/**`, or open a PR against
  `main`/`master`, touching anything under `mobile/**` or `.github/**`.
- **Manually** — go to **Actions → CI → Run workflow**, pick a branch (and optionally
  a flavor), then **Run workflow**.

## 5. Downloading APK / AAB artifacts

1. Open the **Actions** tab and click the CI run you want.
2. Scroll to the **Artifacts** section at the bottom of the run summary.
3. Download `cashnest-<flavor>-apk` or `cashnest-<flavor>-aab` (a `.zip` containing the
   binary). CI artifacts are retained for 14 days.

## 6. Publishing a Release

Either path produces a signed production APK + AAB attached to the Release:

**Option A — push a version tag**

```bash
git tag v1.0.0
git push origin v1.0.0
```

`softprops/action-gh-release@v2` creates the Release (if it doesn't exist) and
attaches `cashnest-v1.0.0.apk` and `cashnest-v1.0.0.aab`.

**Option B — publish from the UI**

Go to **Releases → Draft a new release**, choose/create a tag (e.g. `v1.0.0`),
fill in the notes, and **Publish**. The Release workflow builds and attaches the
artifacts automatically.

## 7. Local reproduction (optional)

The pipeline mirrors these commands (see [`mobile/RELEASE.md`](../mobile/RELEASE.md)):

```bash
cd mobile
flutter pub get
flutter analyze
flutter test
flutter build apk       --release --flavor production --dart-define=APP_ENV=production
flutter build appbundle --release --flavor production --dart-define=APP_ENV=production
```

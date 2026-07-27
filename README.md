# CashNest

[![CI](https://github.com/SouvikPati11/CashNest/actions/workflows/ci.yml/badge.svg)](https://github.com/SouvikPati11/CashNest/actions/workflows/ci.yml)
[![Release](https://github.com/SouvikPati11/CashNest/actions/workflows/release.yml/badge.svg)](https://github.com/SouvikPati11/CashNest/actions/workflows/release.yml)

CashNest is a rewards / earn-and-withdraw mobile app: a Flutter client backed by a
service API, with offerwalls, referrals, withdrawals, and push notifications.

## Repository layout

| Path | Description |
|------|-------------|
| [`mobile/`](mobile) | Flutter application (Riverpod, GoRouter, Hive, Firebase) |
| [`public_html/`](public_html) | Backend service (PHP API + admin) — deploys as the web root on shared hosting |
| [`ARCHITECTURE.md`](ARCHITECTURE.md) | System architecture |
| [`API_SPECIFICATION.md`](API_SPECIFICATION.md) | API contract |
| [`DATABASE_DESIGN.md`](DATABASE_DESIGN.md) | Data model |
| [`UI_UX_DESIGN_SYSTEM.md`](UI_UX_DESIGN_SYSTEM.md) | Design system |

## Build & release from GitHub — no local PC required

Everything builds on GitHub Actions. See **[docs/CICD.md](docs/CICD.md)** for the full
guide (secrets, triggering builds, downloading artifacts, publishing releases).

- **CI** ([`ci.yml`](.github/workflows/ci.yml)) — on every push/PR: `flutter analyze`,
  `flutter test`, then builds a **release APK + AAB for each flavor**
  (`dev` / `staging` / `production`) and uploads them as artifacts.
- **Release** ([`release.yml`](.github/workflows/release.yml)) — publishing a GitHub
  Release or pushing a `v*` tag builds the signed **production** APK + AAB and attaches
  them to the Release.

### Quick start

- **Build now:** Actions → **CI** → **Run workflow**.
- **Download builds:** open a CI run → **Artifacts** → `cashnest-<flavor>-apk` / `-aab`.
- **Cut a release:** `git tag v1.0.0 && git push origin v1.0.0`.

Android release signing uses four repository secrets — `ANDROID_KEYSTORE_BASE64`,
`ANDROID_KEY_ALIAS`, `ANDROID_KEY_PASSWORD`, `ANDROID_STORE_PASSWORD`. The keystore is
**never committed**; builds without the secrets fall back to debug signing. Setup steps
are in [docs/CICD.md](docs/CICD.md#3-android-signing-via-github-secrets).

## Local development

Flutter app details, flavors, Firebase, and signing are documented in
[`mobile/RELEASE.md`](mobile/RELEASE.md).

```bash
cd mobile
flutter pub get
flutter run --dart-define=APP_ENV=development --flavor dev
```

# Settings Module

Account, preferences, and about/support surfaces. Built on the existing
foundation and completed modules **without modifying any of them**.

## What it does

- **Settings hub** (`presentation/settings_screen.dart`) — grouped entry points.
- **Profile** — `GET /v1/profile` (avatar, name, email, referral code, country,
  KYC status, member-since).
- **Edit Profile** — `PUT /v1/profile` (name, country; email is read-only per the
  API). Avatar is displayed read-only (no image-picker dependency in the app).
- **Language** — switches the app locale at runtime via the foundation
  `localeControllerProvider` and persists to `PUT /v1/settings`.
- **Theme** — switches theme via the foundation `themeModeControllerProvider`
  and persists to `PUT /v1/settings`.
- **Notification Settings** — reuses the completed Notification module's
  `NotificationPreferencesScreen` (not modified).
- **About / Privacy Policy / Terms** — `GET /v1/cms/{slug}` (`about`,
  `privacy-policy`, `terms`).
- **FAQ** — `GET /v1/support/faqs`, grouped, expandable.
- **Support** — `GET /v1/support/tickets` (paginated, status filter),
  `POST /v1/support/tickets` (create), `GET /v1/support/tickets/{uuid}` (thread),
  `POST /v1/support/tickets/{uuid}/reply`.
- **App Version** — `GET /v1/app/version` (latest, update/force-update gate,
  changelog, store link) alongside the installed build from the foundation
  `AppInfoService`.
- **Logout** — confirmation dialog then the completed Auth module's
  `authControllerProvider.logout()` (not modified).

## Features

Pull-to-refresh, cursor **infinite pagination** (support tickets), **filters**
(ticket status), **skeleton loading**, **empty**, **error + retry**, and the
global **offline** banner. Material 3, responsive, dark-mode aware, localized
(English + Bangla).

## Reuse

- Foundation: `ApiClient`, `AppScaffold`, `ErrorView`, `EmptyView`,
  `LoadingWidget`, context extensions, theme tokens, `localeController`,
  `themeModeController`, `AppInfoService`.
- Completed modules: the offerwall shared paginated infrastructure
  (`PaginatedListController`/`State`, `PaginatedListView`, `PageResult`), the
  Notification preferences screen, and the Auth logout flow — all reused, none
  modified. Links (store URL) are copied to the clipboard, consistent with the
  other modules (no external-launcher dependency).

## Integration (one line)

```dart
import '../../features/settings/presentation/settings_screen.dart';

GoRoute(path: '/settings', name: 'settings', builder: (_, __) => const SettingsScreen()),
```

Sub-screens are pushed via `Navigator`.

## Tests

- `test/features/settings/settings_models_test.dart`
- `test/features/settings/profile_controller_test.dart`
- `test/features/settings/edit_profile_controller_test.dart`
- `test/features/settings/support_tickets_controller_test.dart`

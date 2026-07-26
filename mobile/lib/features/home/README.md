# Home Module

The Home Dashboard: a server-driven, pull-to-refresh landing screen built on the
existing Flutter foundation and Authentication modules. Neither the foundation
nor the Authentication module is modified by this feature.

## What it does

- **Home Dashboard** (`presentation/home_dashboard_screen.dart`) — composes the
  profile header, balance card, dynamic layout sections, and fixed feature
  teasers into one scrollable screen.
- **Dynamic Home Layout (API-driven)** — `GET /v1/home/layout` returns ordered
  sections; `SectionRenderer` maps each `type` to a widget. Unknown/newer types
  render nothing, so the layout is forward-compatible.
- **Balance Card** — `GET /v1/wallet` projected read-only (the Wallet feature is
  a separate module).
- **User Profile Header** — greeting + avatar/initials from the authenticated
  user (`authControllerProvider`).
- **Banner Carousel** — auto-advancing `GET /v1/banners?filter[placement]=home_top`.
- **Announcement Popup** — highest-priority `GET /v1/announcements`; dismissing
  persists via `POST /v1/announcements/{id}/seen`.
- **Quick Actions** — shortcut grid driven by the layout section config.
- **Recent Transactions Preview** — last 5 from `GET /v1/wallet/transactions`.
- **Feature teasers** — Daily Check-in, Scratch, Spin, Offerwall, Tasks,
  Referral, Leaderboard (UI-only previews; the real features are separate
  modules).
- **States** — skeleton loading, error + retry, empty, and the global offline
  banner (via `AppScaffold`).
- **Pull-to-refresh**, **Material 3 UI**, **responsive** layout,
  **dark mode** (inherited from the foundation theme), and **localization**
  (English + Bangla via `l10n/home_strings.dart`).

## Architecture

```
models/            plain immutable models + fromJson (no codegen)
data/              HomeRepository (interface) + HomeRepositoryImpl (over ApiClient)
application/        HomeController (StateNotifier<AsyncValue<HomeData>>)
providers/          Riverpod wiring (reuses apiClientProvider + authControllerProvider)
l10n/              HomeStrings (en/bn)
presentation/      HomeDashboardScreen + widgets/
```

`layout` and `balance` are required — a failure surfaces an error state with
retry. Banners, announcements, and recent transactions are best-effort and
degrade to empty so a partial outage still renders a useful dashboard.

## Integration (one line)

The module ships self-contained and is wired by pointing the existing `/home`
route at `HomeDashboardScreen`. In `lib/core/router/app_router.dart`:

```dart
// import
import '../../features/home/presentation/home_dashboard_screen.dart';

// in the /home GoRoute builder, replace HomeScreen with:
builder: (context, state) => const HomeDashboardScreen(),
```

The foundation's placeholder `home_screen.dart` is left untouched for reference
and can be removed once the route is switched.

Action taps (quick actions, teasers, banners, announcement CTAs) currently show
a "coming soon" snackbar; wire them to the corresponding routes as those feature
modules land.

## Tests

- `test/features/home/home_models_test.dart` — JSON parsing / forward-compat.
- `test/features/home/home_controller_test.dart` — aggregation, required-vs-
  ancillary failure handling, announcement dismissal.
- `test/features/home/home_widgets_test.dart` — balance formatting, preview card
  CTA, quick actions, recent-transactions rendering + empty state.

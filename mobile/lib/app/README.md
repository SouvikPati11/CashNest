# App Integration Layer

The composition root that wires every completed feature module into a single
running app. No feature logic is rebuilt here — this layer only integrates.

## Structure

```
main.dart            → bootstrap()
app/bootstrap.dart   → infra init + ProviderScope overrides (DI) + runApp
app/app.dart         → MaterialApp.router (theme, locale, localization, router)
app/router/
  app_route_paths.dart → every route path + name (single source of truth)
  app_router.dart      → appRouterProvider: the integrated GoRouter
  home_shell.dart      → bottom-navigation shell (5 tabs)
```

## Routing (tasks 1, 2, 9, 15, 16, 20)

`appRouterProvider` is the one router the app uses (`app.dart` watches it). It
replaces the foundation `goRouterProvider` and the auth module's
`authRouterProvider` — both of those, the placeholder `HomeScreen`
(foundation demo), and the foundation `SplashScreen` were **removed**.

Flow: `/` → `AuthSplashScreen` (restores the session) → the auth guard routes to
`/home` (authenticated) or `/login` (unauthenticated).

- **Pre-auth:** `/login`, `/register`, `/verify`, `/forgot`.
- **Bottom-nav shell** (`StatefulShellRoute.indexedStack`, each tab keeps its
  own stack): `/home` (HomeDashboardScreen), `/rewards` (RewardsScreen),
  `/wallet` (WalletScreen), `/earn` (OfferwallScreen), `/settings`
  (SettingsScreen).
- **Full-screen (above the shell):** `/referral`, `/withdraw`, `/notifications`,
  `/error`.

Deep links resolve by path; unauthenticated deep links to any protected route
redirect to `/login` (see `test/app/router_guard_test.dart`).

## Auth guard (tasks 4, 16)

Reuses the auth module's pure `authRedirect(status, location)` as the router
`redirect`, re-evaluated on every `authControllerProvider` change via
`refreshListenable`. `unknown` holds the splash; `unauthenticated` allows only
auth routes; `authenticated` bounces splash/auth to `/home`.

## Cross-cutting wiring

- **Unread badge (task 5):** the Home app bar hosts `UnreadBadge` (Notification
  module) → `context.push('/notifications')`; the badge tracks
  `unreadCountProvider`, invalidated after read mutations.
- **Theme switching (task 6):** `app.dart` watches `themeModeControllerProvider`;
  the Settings → Theme screen calls `.set(...)` on it (and persists to the
  backend).
- **Language switching (task 7):** `app.dart` watches `localeControllerProvider`;
  the Settings → Language screen calls `.set(...)`.
- **Logout (task 8):** Settings → Log out calls `authControllerProvider.logout()`;
  the auth-state change drives the guard back to `/login`.

## DI, providers, repositories, controllers (tasks 10–14)

Riverpod providers are global; importing the feature screens (via the router)
makes every feature repository/controller/provider reachable — no central
registry is needed. Infrastructure DI lives in `bootstrap.dart`'s
`ProviderScope` overrides (config, logger, preferences, cache, analytics).

## Localization delegates (task 11)

The app registers `AppLocalizations.delegate` + the Global Material/Widgets/
Cupertino delegates. Feature localization is **delegate-free by design**: each
feature exposes a `*Strings.of(context)` class that reads
`Localizations.localeOf(context)` (English + Bangla), so it needs no separate
delegate — it rides the single app delegate + locale.

## Verification

`flutter analyze` / `flutter test` could not be executed in this environment
(no Flutter SDK installed). `test/app/router_guard_test.dart` covers route-guard
coverage for every feature route and route-constant alignment.

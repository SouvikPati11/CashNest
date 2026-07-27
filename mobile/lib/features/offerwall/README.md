# Offerwall + CPA Feature

Offer catalogs (offerwall + CPA), offer clicks, and offerwall/CPA earning
history. Part of the "Offerwall + Referral" module, built on the existing
foundation and completed modules **without modifying any of them**.

## What it does

- **Offerwall Screen** (`presentation/offerwall_screen.dart`) — Offers, CPA, and
  History tabs.
- **Offers** — `GET /offerwall/offers` (paginated, `filter[provider|category]`).
- **CPA Offers** — `GET /cpa/offers` (paginated, `filter[provider|country]`).
- **Offer detail + click** — `GET /offerwall/offers/{uuid}`,
  `POST /offerwall/offers/{uuid}/click` (returns the tracking link, copied to
  the clipboard — the app has no external-browser launcher dependency).
- **Offerwall History** — reads the wallet ledger filtered to offerwall/CPA
  credit rows (`GET /wallet/transactions?filter[type]=offerwall,cpa`), without
  importing or modifying the Wallet module.
- **Providers** — `GET /offerwall/providers` (for filter chips).

## Features

Pull-to-refresh, cursor **infinite pagination**, client-side **search**,
server-side **filters** (bottom sheet), **skeleton loading**, **empty**,
**error + retry**, and the global **offline** banner (via `AppScaffold`).
Material 3, responsive, dark-mode aware, localized (English + Bangla).

## Shared infrastructure

This feature hosts the module's reusable paginated-list infrastructure, which
the referral feature also uses:

- `application/paginated_list_controller.dart` + `paginated_list_state.dart` —
  a generic `StateNotifier` base with first-page load, cursor infinite scroll,
  pull-to-refresh, and client-side search (subclasses supply `fetchPage` and an
  optional `matchesSearch`).
- `presentation/widgets/paginated_list_view.dart` — the matching list UI
  (skeleton → error/empty → list + infinite-scroll footer).
- `models/page_result.dart`, `presentation/widgets/{list_skeleton,search_field}.dart`.

Paginated endpoints need `meta.pagination` (which the shared `ApiClient` unwraps
away), so the repository reads those via the exposed `ApiClient.raw` Dio and
reuses `DioErrorMapper` — the foundation is not modified.

## Integration (one line)

Add a route pointing at `OfferwallScreen`:

```dart
import '../../features/offerwall/presentation/offerwall_screen.dart';

GoRoute(path: '/earn', name: 'earn', builder: (_, __) => const OfferwallScreen()),
```

## Tests

- `test/features/offerwall/offerwall_models_test.dart`
- `test/features/offerwall/offer_filter_test.dart`
- `test/features/offerwall/paginated_list_controller_test.dart`
- `test/features/offerwall/offers_controller_test.dart`

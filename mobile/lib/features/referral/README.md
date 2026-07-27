# Referral + Leaderboard Feature

Referral info & sharing, referred-users list, commission earnings, code
application, and the leaderboard. Part of the "Offerwall + Referral" module,
built on the existing foundation and completed modules **without modifying any
of them**.

## What it does

- **Referral Screen** (`presentation/referral_screen.dart`) — a referral info
  header (code, sharing, stats) plus Referrals / Earnings / Leaderboard tabs.
- **Referral info + sharing** — `GET /referral`; copy code / copy link to the
  clipboard (no external share dependency).
- **Referral History (referred users)** — `GET /referral/list` (paginated,
  `filter[status]`), with client-side name search.
- **Earnings** — `GET /referral/earnings` (paginated commission history).
- **Apply code** — `POST /referral/apply` (dialog).
- **Referral Leaderboard** — `GET /leaderboard` (paginated, `filter[period]`)
  and `GET /leaderboard/me` for the caller's rank, with a period selector and
  client-side name search.

## Features

Pull-to-refresh, cursor **infinite pagination**, client-side **search**,
**filters** (status chips / period selector), **skeleton loading**, **empty**,
**error + retry**, and the global **offline** banner. Material 3, responsive,
dark-mode aware, localized (English + Bangla).

## Shared infrastructure

This feature reuses the module's paginated-list infrastructure that lives in the
offerwall feature (`features/offerwall/application/paginated_list_controller.dart`,
`.../presentation/widgets/paginated_list_view.dart`, `models/page_result.dart`,
`search_field.dart`, `list_skeleton.dart`) and its `OfferFormatters`. The two
features form a single delivered module, so sharing this internal infra keeps it
DRY without touching any completed module. Paginated endpoints are read via the
exposed `ApiClient.raw` for `meta.pagination`.

## Integration (one line)

Add a route pointing at `ReferralScreen`:

```dart
import '../../features/referral/presentation/referral_screen.dart';

GoRoute(path: '/refer', name: 'refer', builder: (_, __) => const ReferralScreen()),
```

## Tests

- `test/features/referral/referral_models_test.dart`
- `test/features/referral/referral_info_controller_test.dart`
- `test/features/referral/referral_list_controller_test.dart`
- `test/features/referral/leaderboard_controller_test.dart`

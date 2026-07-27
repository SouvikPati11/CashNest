# Wallet Module

The Wallet feature: balance summary, coin→cash conversion, and the full
transaction ledger with filtering, search, and infinite scroll. Built on the
existing Flutter foundation, Authentication, and Home modules **without
modifying any of them**.

## What it does

- **Wallet Screen** (`presentation/wallet_screen.dart`) — balance/conversion
  header followed by the transaction history in one pull-to-refresh scroll view.
- **Balance Summary / Coin Balance / Cash Balance** — `GET /v1/wallet`
  (coin balance, available, reserved, cash value, lifetime earned/spent).
- **Conversion Card** — `GET /v1/wallet/conversion` (rate + min/max withdraw).
  Best-effort: the balance still renders if the conversion call fails.
- **Transaction History** — `GET /v1/wallet/transactions`, cursor-paginated.
- **Transaction Details** — `GET /v1/wallet/transactions/{uuid}` (metadata +
  related transaction), pushed as its own screen.
- **Filters** — type, direction (credit/debit), and date range via a Material 3
  bottom sheet, mapped to the backend `filter[...]` standard.
- **Pagination + Infinite Scroll** — cursor-based; the next page loads as the
  user nears the bottom, with an inline retry on load-more failure.
- **Pull-to-refresh**, **Search** (client-side over loaded transactions),
  **Skeleton loading**, **Empty state** (distinct "no transactions" vs
  "no matches"), **Error state** (with retry), and the global **offline banner**
  (via `AppScaffold`).
- **Material 3**, **responsive** (width-constrained on tablets), **dark mode**
  (foundation theme), and **localized** (English + Bangla).

## Architecture

```
models/            WalletSummary, ConversionRate, WalletTransaction,
                   TransactionFilter, TransactionPage, WalletOverview
data/              WalletRepository (interface) + WalletRepositoryImpl
application/        WalletSummaryController (AsyncValue<WalletOverview>),
                   TransactionsController (+ TransactionsState)
providers/          Riverpod wiring (reuses apiClientProvider)
l10n/              WalletStrings (en/bn)
resources/          WalletFormatters (intl-based coins/cash/date formatting)
presentation/      WalletScreen, TransactionDetailScreen, widgets/
```

### Pagination note

The transaction list needs `meta.pagination` from the response envelope, which
the shared `ApiClient` unwraps away (it exposes only `data`). The repository
therefore reads that one endpoint through the foundation's exposed
`ApiClient.raw` Dio and reuses `DioErrorMapper` for error translation — the
foundation is not modified. All other calls use `ApiClient` normally.

### Search note

Search filters the already-loaded transactions client-side (description, type,
and source module). The backend transaction endpoint does not define a
free-text search param; server-side type/direction/date filtering is available
through the filter sheet.

## Integration (one line)

The module ships self-contained. Wire it by adding a `/wallet` route pointing at
`WalletScreen` (and letting the Home quick actions / "view all" navigate to it).
In `lib/core/router/app_router.dart`:

```dart
import '../../features/wallet/presentation/wallet_screen.dart';

GoRoute(
  path: '/wallet',
  name: 'wallet',
  builder: (context, state) => const WalletScreen(),
),
```

The transaction detail screen is pushed directly via `Navigator` from a tapped
row, so it needs no route registration (a `GoRoute` can be added later if deep
linking is required).

## Tests

- `test/features/wallet/wallet_models_test.dart` — JSON parsing, filter query
  mapping, conversion math.
- `test/features/wallet/wallet_summary_controller_test.dart` — summary required
  vs conversion best-effort.
- `test/features/wallet/transactions_controller_test.dart` — first page,
  infinite-scroll append, filter reload, load-more error, client-side search.
- `test/features/wallet/wallet_widgets_test.dart` — balance formatting,
  transaction tile, empty state.

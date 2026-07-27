# Withdraw Feature

Cash-out flow: payout methods, a withdrawal form with a live coin→cash / fee
preview, review + submit, paginated history, detail, and a status timeline.
Built on the existing foundation and completed modules **without modifying any
of them**.

## What it does

- **Withdraw Methods** — `GET /v1/withdraw/methods` (limits, fee, dynamic
  `detail_schema` driving the payment-detail fields).
- **Withdraw Form** (`presentation/withdraw_screen.dart`) — method picker,
  amount, dynamic payment-detail fields, min/max hints, client-side validation.
- **Live Coin → Cash Conversion + Fee Calculation** — computed client-side from
  `GET /v1/wallet/conversion` (rate) and the method fee, shown in a live summary
  (server recomputes authoritatively on submit).
- **Summary Screen** — a review bottom sheet before confirming.
- **Create request** — `POST /v1/withdraw/request` with the required
  `X-Idempotency-Key`.
- **Withdraw History** — `GET /v1/withdraw/history` (paginated, status filter).
- **Withdraw Detail + Status Timeline** — `GET /v1/withdraw/{uuid}` and cancel
  via `POST /v1/withdraw/{uuid}/cancel`.

## Features

Pull-to-refresh, cursor **infinite pagination**, **filters** (status),
**skeleton loading**, **empty**, **error + retry**, and the global **offline**
banner. Material 3, responsive, dark-mode aware, localized (English + Bangla).

## Reuse

Reuses the shared paginated infrastructure from the offerwall feature
(`PaginatedListController`/`State`, `PaginatedListView`, `PageResult`,
`ListSkeleton`) for the history list, per the "reuse previously completed
modules" instruction. The paginated endpoint is read via the exposed
`ApiClient.raw` for `meta.pagination`. The Wallet module is not imported — the
conversion rate is read directly from `/wallet/conversion`.

## Integration (one line)

```dart
import '../../features/withdraw/presentation/withdraw_screen.dart';

GoRoute(path: '/withdraw', name: 'withdraw', builder: (_, __) => const WithdrawScreen()),
```

History and detail screens are pushed via `Navigator`.

## Tests

- `test/features/withdraw/withdraw_models_test.dart`
- `test/features/withdraw/withdraw_quote_test.dart`
- `test/features/withdraw/withdraw_form_controller_test.dart`
- `test/features/withdraw/withdraw_history_controller_test.dart`

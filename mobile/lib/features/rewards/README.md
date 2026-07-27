# Rewards Module

Gamified earning: daily check-in, scratch cards, spin wheel, tasks, and a merged
reward history. Built on the existing Flutter foundation, Authentication, Home,
and Wallet modules **without modifying any of them**.

## What it does

- **Rewards hub** (`presentation/rewards_screen.dart`) — a responsive grid of
  entry points to each sub-feature.
- **Daily Check-in** — `GET /v1/checkin/status` + `/calendar`,
  `POST /v1/checkin/claim`. Streak header, reward ladder (progress indicator),
  and an animated claim.
- **Scratch Card** — `GET /v1/scratch/available`, `POST /v1/scratch/{uuid}/reveal`
  then `/claim`. The card is revealed server-side, then a real scratch-off
  gesture (foil mask via `CustomPainter` + `BlendMode.clear`) uncovers it and
  auto-claims.
- **Spin Wheel** — `GET /v1/spin/status`, `POST /v1/spin`. A `CustomPainter`
  wheel animates to the **server-chosen** segment (the client never decides the
  outcome), then shows the reward.
- **Tasks** — `GET /v1/tasks`, `POST /v1/tasks/{uuid}/start` + `/complete`.
  Start → complete flow with per-user progress; auto-verified completions show
  the reward, manual ones show a "pending review" message.
- **Reward History** — merges `GET /v1/scratch/history`, `/spin/history`, and
  `/tasks/history` into one recency-sorted list (each source best-effort).
- **Animations** — shared claim animation (`reward_result_dialog.dart`), scratch
  animation, and spin animation.
- **States** — skeleton loading, empty, error + retry, and the global offline
  banner (via `AppScaffold`); every screen supports pull-to-refresh.
- **Material 3**, **responsive**, **dark mode** (foundation theme), and
  **localized** (English + Bangla).

## Architecture

```
models/            CheckinStatus, CheckinCalendar, ClaimResult, ScratchCard,
                   SpinStatus/SpinSegment, SpinResult, RewardTask,
                   TaskActionResult, RewardHistoryEntry
data/              RewardsRepository (interface) + RewardsRepositoryImpl
services/          IdempotencyKey (X-Idempotency-Key generator)
application/        Checkin/Scratch/Spin/Tasks/RewardHistory controllers
providers/          Riverpod wiring (reuses apiClientProvider)
l10n/              RewardsStrings (en/bn)
resources/          RewardVisuals (hex→Color, coin/date formatting)
presentation/      hub + 5 screens + widgets/
```

Reward-crediting mutations (check-in claim, scratch reveal/claim, spin, task
complete) send an `X-Idempotency-Key` so retries don't double-submit; the
backend remains the source of truth for single-crediting.

Dates are formatted manually (not via `intl`'s `DateFormat`) because the app
does not initialize `intl` locale date-symbol data; coin counts use `intl`'s
`NumberFormat`, which is safe without initialization.

## Integration (one line)

The module ships self-contained. Wire it by adding a `/rewards` route pointing at
`RewardsScreen` (and letting the Home quick actions navigate to it). In
`lib/core/router/app_router.dart`:

```dart
import '../../features/rewards/presentation/rewards_screen.dart';

GoRoute(
  path: '/rewards',
  name: 'rewards',
  builder: (context, state) => const RewardsScreen(),
),
```

Sub-screens are pushed via `Navigator` from the hub, so they need no route
registration (routes can be added later for deep linking).

## Tests

- `test/features/rewards/rewards_models_test.dart` — JSON parsing across all
  models + spin-segment sorting.
- `test/features/rewards/reward_visuals_test.dart` — hex parsing, coin/date
  formatting.
- `test/features/rewards/checkin_controller_test.dart` — load + claim, error.
- `test/features/rewards/scratch_controller_test.dart` — reveal replaces, claim
  removes.
- `test/features/rewards/spin_controller_test.dart` — spin decrements remaining.
- `test/features/rewards/tasks_controller_test.dart` — complete bumps count on
  credited.
- `test/features/rewards/reward_history_controller_test.dart` — merge + sort +
  best-effort failures.

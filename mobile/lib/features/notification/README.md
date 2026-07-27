# Notification Feature

In-app inbox, detail, read mutations, an unread badge, and notification
preferences. Built on the existing foundation and completed modules **without
modifying any of them**.

## What it does

- **Notification List** (`presentation/notification_screen.dart`) — paginated
  inbox (`GET /v1/notifications`) with an All / Unread filter.
- **Notification Detail** — marks the item read on open; shows image + deep link
  (copied to clipboard, no external launcher dependency).
- **Mark as Read** — `POST /v1/notifications/{uuid}/read`.
- **Mark All as Read** — `POST /v1/notifications/read-all`.
- **Unread Badge** — `UnreadBadge` widget driven by
  `GET /v1/notifications/unread-count` (`unreadCountProvider`), invalidated after
  read mutations.
- **Notification Preferences** — reads/writes only the notification fields of
  `GET/PUT /v1/settings` (push / transactional / promotional) with optimistic
  updates. The Settings module is not built or modified.

## Features

Pull-to-refresh, cursor **infinite pagination**, **filters** (unread-only),
**skeleton loading**, **empty**, **error + retry**, and the global **offline**
banner. Material 3, responsive, dark-mode aware, localized (English + Bangla).

## Reuse

Reuses the shared paginated infrastructure from the offerwall feature
(`PaginatedListController`/`State`, `PaginatedListView`, `PageResult`,
`ListSkeleton`). The inbox is read via the exposed `ApiClient.raw` for
`meta.pagination`.

## Integration (one line)

```dart
import '../../features/notification/presentation/notification_screen.dart';

GoRoute(path: '/notifications', name: 'notifications',
  builder: (_, __) => const NotificationScreen()),
```

Wrap a notifications icon with `UnreadBadge` to show the live unread count.

## Tests

- `test/features/notification/notification_models_test.dart`
- `test/features/notification/notifications_controller_test.dart`
- `test/features/notification/preferences_controller_test.dart`

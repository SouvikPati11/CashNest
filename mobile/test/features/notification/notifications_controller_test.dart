import 'package:cashnest/features/notification/application/notifications_controller.dart';
import 'package:cashnest/features/offerwall/application/paginated_list_state.dart';
import 'package:flutter_test/flutter_test.dart';

import 'fake_notification_repository.dart';

void main() {
  late FakeNotificationRepository repo;

  setUp(() => repo = FakeNotificationRepository());

  test('load populates the inbox', () async {
    final c = NotificationsController(repo);
    await c.load();
    expect(c.debugState.status, ListStatus.ready);
    expect(c.debugState.items, hasLength(2));
    c.dispose();
  });

  test('setUnreadOnly reloads with the is_read filter', () async {
    final c = NotificationsController(repo);
    await c.load();
    await c.setUnreadOnly(true);
    expect(c.unreadOnly, isTrue);
    expect(repo.lastIsReadFilter, isFalse);
    c.dispose();
  });

  test('markRead flips the item locally', () async {
    final c = NotificationsController(repo);
    await c.load();
    final ok = await c.markRead('n1');
    expect(ok, isTrue);
    expect(repo.markReadCalls, 1);
    expect(c.debugState.items.firstWhere((n) => n.uuid == 'n1').isRead, isTrue);
    expect(c.debugState.items.firstWhere((n) => n.uuid == 'n2').isRead, isFalse);
    c.dispose();
  });

  test('markAllRead flips all items locally', () async {
    final c = NotificationsController(repo);
    await c.load();
    await c.markAllRead();
    expect(repo.markAllCalls, 1);
    expect(c.debugState.items.every((n) => n.isRead), isTrue);
    c.dispose();
  });
}

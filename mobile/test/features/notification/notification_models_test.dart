import 'package:cashnest/features/notification/models/app_notification.dart';
import 'package:cashnest/features/notification/models/notification_preferences.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('AppNotification parses fields + copyWith flips isRead', () {
    final n = AppNotification.fromJson(const {
      'uuid': 'n1',
      'title': 'Welcome',
      'body': 'Hi there',
      'type': 'system',
      'deep_link': 'app://home',
      'is_read': false,
      'created_at': '2026-07-24T18:00:00Z',
    });
    expect(n.uuid, 'n1');
    expect(n.isRead, isFalse);
    expect(n.deepLink, 'app://home');
    expect(n.createdAt, isNotNull);
    expect(n.copyWith(isRead: true).isRead, isTrue);
  });

  test('NotificationPreferences parses from a preferences wrapper', () {
    final p = NotificationPreferences.fromJson(const {
      'preferences': {
        'notif_push_enabled': false,
        'notif_transactional': true,
        'notif_promotional': false,
      },
    });
    expect(p.pushEnabled, isFalse);
    expect(p.transactional, isTrue);
    expect(p.promotional, isFalse);
  });

  test('NotificationPreferences parses from a top-level payload', () {
    final p = NotificationPreferences.fromJson(const {
      'notif_push_enabled': true,
      'notif_promotional': false,
    });
    expect(p.pushEnabled, isTrue);
    expect(p.promotional, isFalse);
    expect(p.transactional, isTrue); // default
  });

  test('toJson emits only the notification fields', () {
    const p = NotificationPreferences(pushEnabled: true, transactional: false, promotional: true);
    expect(p.toJson(), {
      'notif_push_enabled': true,
      'notif_transactional': false,
      'notif_promotional': true,
    });
  });
}

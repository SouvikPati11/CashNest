import 'package:cashnest/core/error/app_exception.dart';
import 'package:cashnest/core/network/api_result.dart';
import 'package:cashnest/features/notification/application/preferences_controller.dart';
import 'package:cashnest/features/notification/models/notification_preferences.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

import 'fake_notification_repository.dart';

void main() {
  late FakeNotificationRepository repo;

  setUp(() => repo = FakeNotificationRepository());

  test('load fetches preferences', () async {
    final c = PreferencesController(repo);
    await c.load();
    expect(c.debugState.valueOrNull, isNotNull);
    expect(c.debugState.valueOrNull!.pushEnabled, isTrue);
    c.dispose();
  });

  test('setPromotional persists the update from the server response', () async {
    repo.updateResult = const ApiResult.success(
      NotificationPreferences(promotional: false),
    );
    final c = PreferencesController(repo);
    await c.load();
    await c.setPromotional(false);
    expect(repo.lastUpdated?.promotional, isFalse);
    expect(c.debugState.valueOrNull!.promotional, isFalse);
    c.dispose();
  });

  test('failed update reverts to the previous value', () async {
    repo.updateResult = const ApiResult.failure(ServerException('boom', statusCode: 500));
    final c = PreferencesController(repo);
    await c.load();
    final before = c.debugState.valueOrNull!.promotional;
    await c.setPromotional(!before);
    expect(c.debugState.valueOrNull!.promotional, before);
    c.dispose();
  });
}

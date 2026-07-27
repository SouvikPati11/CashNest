import 'package:cashnest/core/error/app_exception.dart';
import 'package:cashnest/core/network/api_result.dart';
import 'package:cashnest/features/settings/application/profile_controller.dart';
import 'package:cashnest/features/settings/models/user_profile.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

import 'fake_settings_repository.dart';

void main() {
  late FakeSettingsRepository repo;

  setUp(() => repo = FakeSettingsRepository());

  test('load fetches the profile', () async {
    final c = ProfileController(repo);
    await c.load();
    expect(c.debugState.valueOrNull?.name, 'Asha');
    c.dispose();
  });

  test('profile failure surfaces an error state', () async {
    repo.profile = const ApiResult.failure(ServerException('boom', statusCode: 500));
    final c = ProfileController(repo);
    await c.load();
    expect(c.debugState, isA<AsyncError<dynamic>>());
    c.dispose();
  });

  test('setProfile replaces the cached profile without a refetch', () async {
    final c = ProfileController(repo);
    await c.load();
    c.setProfile(const UserProfile(uuid: 'u1', name: 'Renamed', email: 'asha@x.io'));
    expect(c.debugState.valueOrNull?.name, 'Renamed');
    c.dispose();
  });
}

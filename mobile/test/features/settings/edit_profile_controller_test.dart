import 'package:cashnest/core/error/app_exception.dart';
import 'package:cashnest/core/network/api_result.dart';
import 'package:cashnest/features/settings/application/edit_profile_controller.dart';
import 'package:cashnest/features/settings/models/user_profile.dart';
import 'package:flutter_test/flutter_test.dart';

import 'fake_settings_repository.dart';

const _profile = UserProfile(uuid: 'u1', name: 'Asha', email: 'asha@x.io', countryCode: 'IN');

void main() {
  late FakeSettingsRepository repo;

  setUp(() => repo = FakeSettingsRepository());

  test('initializes fields from the profile', () {
    final c = EditProfileController(repo, _profile);
    expect(c.debugState.name, 'Asha');
    expect(c.debugState.countryCode, 'IN');
    expect(c.debugState.canSave, isTrue);
    c.dispose();
  });

  test('canSave is false for a too-short name', () {
    final c = EditProfileController(repo, _profile);
    c.setName('A');
    expect(c.debugState.canSave, isFalse);
    c.dispose();
  });

  test('save sends trimmed name and uppercased country', () async {
    final c = EditProfileController(repo, _profile);
    c.setName('  Asha Rao  ');
    c.setCountryCode('us');
    final updated = await c.save();
    expect(updated.name, 'Asha Rao');
    expect(repo.lastName, 'Asha Rao');
    expect(repo.lastCountry, 'US');
    c.dispose();
  });

  test('save records error and rethrows on failure', () async {
    repo.updateResult = const ApiResult.failure(ValidationException('bad'));
    final c = EditProfileController(repo, _profile);
    c.setName('Valid Name');
    await expectLater(c.save(), throwsA(isA<AppException>()));
    expect(c.debugState.error, isNotNull);
    expect(c.debugState.saving, isFalse);
    c.dispose();
  });
}

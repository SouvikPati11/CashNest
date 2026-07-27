import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_result.dart';
import '../data/settings_repository.dart';
import '../models/user_profile.dart';

/// Loads the signed-in user's profile.
class ProfileController extends StateNotifier<AsyncValue<UserProfile>> {
  ProfileController(this._repository) : super(const AsyncValue.loading());

  final SettingsRepository _repository;

  Future<void> load() async {
    state = const AsyncValue.loading();
    state = await AsyncValue.guard(_fetch);
  }

  Future<void> refresh() async {
    state = await AsyncValue.guard(_fetch);
  }

  Future<UserProfile> _fetch() async {
    final result = await _repository.fetchProfile();
    return switch (result) {
      ApiSuccess(:final data) => data,
      ApiFailure(:final error) => throw error,
    };
  }

  /// Replace the cached profile after an edit (avoids a refetch).
  void setProfile(UserProfile profile) {
    state = AsyncValue.data(profile);
  }
}

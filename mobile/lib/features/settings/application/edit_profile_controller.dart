import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/error/app_exception.dart';
import '../../../core/network/api_result.dart';
import '../data/settings_repository.dart';
import '../models/user_profile.dart';

/// State for the edit-profile form.
@immutable
class EditProfileState {
  const EditProfileState({
    this.name = '',
    this.countryCode = '',
    this.saving = false,
    this.error,
  });

  final String name;
  final String countryCode;
  final bool saving;
  final AppException? error;

  bool get canSave => name.trim().length >= 2 && !saving;

  EditProfileState copyWith({
    String? name,
    String? countryCode,
    bool? saving,
    AppException? error,
    bool resetError = false,
  }) {
    return EditProfileState(
      name: name ?? this.name,
      countryCode: countryCode ?? this.countryCode,
      saving: saving ?? this.saving,
      error: resetError ? null : (error ?? this.error),
    );
  }
}

/// Drives the edit-profile form and saves via `PUT /v1/profile`.
class EditProfileController extends StateNotifier<EditProfileState> {
  EditProfileController(this._repository, UserProfile initial)
      : super(EditProfileState(
          name: initial.name,
          countryCode: initial.countryCode ?? '',
        ));

  final SettingsRepository _repository;

  void setName(String value) => state = state.copyWith(name: value, resetError: true);

  void setCountryCode(String value) =>
      state = state.copyWith(countryCode: value, resetError: true);

  /// Save the profile; returns the updated [UserProfile] or throws.
  Future<UserProfile> save() async {
    state = state.copyWith(saving: true, resetError: true);
    final country = state.countryCode.trim();
    final result = await _repository.updateProfile(
      name: state.name.trim(),
      countryCode: country.isEmpty ? null : country.toUpperCase(),
    );
    switch (result) {
      case ApiSuccess(:final data):
        state = state.copyWith(saving: false);
        return data;
      case ApiFailure(:final error):
        state = state.copyWith(saving: false, error: error);
        throw error;
    }
  }
}

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_result.dart';
import '../data/notification_repository.dart';
import '../models/notification_preferences.dart';

/// Loads and updates notification preferences with optimistic UI updates.
class PreferencesController extends StateNotifier<AsyncValue<NotificationPreferences>> {
  PreferencesController(this._repository) : super(const AsyncValue.loading());

  final NotificationRepository _repository;

  Future<void> load() async {
    state = const AsyncValue.loading();
    final result = await _repository.fetchPreferences();
    state = switch (result) {
      ApiSuccess(:final data) => AsyncValue.data(data),
      ApiFailure(:final error) => AsyncValue.error(error, StackTrace.current),
    };
  }

  Future<void> refresh() async {
    final result = await _repository.fetchPreferences();
    if (result case ApiSuccess(:final data)) {
      state = AsyncValue.data(data);
    }
  }

  Future<void> _update(NotificationPreferences next) async {
    final previous = state.valueOrNull;
    // Optimistic update.
    state = AsyncValue.data(next);
    final result = await _repository.updatePreferences(next);
    switch (result) {
      case ApiSuccess(:final data):
        state = AsyncValue.data(data);
      case ApiFailure():
        // Revert on failure.
        if (previous != null) {
          state = AsyncValue.data(previous);
        }
    }
  }

  Future<void> setPushEnabled(bool value) {
    final current = state.valueOrNull ?? NotificationPreferences.defaults;
    return _update(current.copyWith(pushEnabled: value));
  }

  Future<void> setTransactional(bool value) {
    final current = state.valueOrNull ?? NotificationPreferences.defaults;
    return _update(current.copyWith(transactional: value));
  }

  Future<void> setPromotional(bool value) {
    final current = state.valueOrNull ?? NotificationPreferences.defaults;
    return _update(current.copyWith(promotional: value));
  }
}

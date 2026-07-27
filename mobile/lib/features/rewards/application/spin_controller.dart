import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_result.dart';
import '../data/rewards_repository.dart';
import '../models/spin_result.dart';
import '../models/spin_status.dart';

/// Loads spin availability/segments and performs a spin.
class SpinController extends StateNotifier<AsyncValue<SpinStatus>> {
  SpinController(this._repository) : super(const AsyncValue.loading());

  final RewardsRepository _repository;

  Future<void> load() async {
    state = const AsyncValue.loading();
    state = await AsyncValue.guard(_fetch);
  }

  Future<void> refresh() async {
    state = await AsyncValue.guard(_fetch);
  }

  Future<SpinStatus> _fetch() async {
    final result = await _repository.fetchSpinStatus();
    return switch (result) {
      ApiSuccess(:final data) => data,
      ApiFailure(:final error) => throw error,
    };
  }

  /// Perform a spin; returns the [SpinResult] (the wheel must land on
  /// [SpinResult.segmentId]) or throws. Decrements the remaining spins.
  Future<SpinResult> spin({String source = 'free'}) async {
    final result = await _repository.spin(source: source);
    return switch (result) {
      ApiSuccess(:final data) => _onSpun(data),
      ApiFailure(:final error) => throw error,
    };
  }

  SpinResult _onSpun(SpinResult result) {
    final current = state.valueOrNull;
    if (current != null) {
      final remaining = result.spinsRemaining ??
          (current.spinsRemaining > 0 ? current.spinsRemaining - 1 : 0);
      state = AsyncValue.data(current.copyWith(spinsRemaining: remaining));
    }
    return result;
  }
}

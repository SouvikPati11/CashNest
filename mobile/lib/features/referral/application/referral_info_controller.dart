import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_result.dart';
import '../data/referral_repository.dart';
import '../models/referral_info.dart';

/// Loads the user's referral info and applies a referral code.
class ReferralInfoController extends StateNotifier<AsyncValue<ReferralInfo>> {
  ReferralInfoController(this._repository) : super(const AsyncValue.loading());

  final ReferralRepository _repository;

  Future<void> load() async {
    state = const AsyncValue.loading();
    state = await AsyncValue.guard(_fetch);
  }

  Future<void> refresh() async {
    state = await AsyncValue.guard(_fetch);
  }

  Future<ReferralInfo> _fetch() async {
    final result = await _repository.fetchInfo();
    return switch (result) {
      ApiSuccess(:final data) => data,
      ApiFailure(:final error) => throw error,
    };
  }

  /// Apply a referral code; returns whether it was applied, or throws the
  /// [AppException] on failure. Refreshes info on success.
  Future<bool> applyCode(String code) async {
    final result = await _repository.applyCode(code);
    return switch (result) {
      ApiSuccess(:final data) => await _onApplied(data),
      ApiFailure(:final error) => throw error,
    };
  }

  Future<bool> _onApplied(bool applied) async {
    if (applied) {
      await refresh();
    }
    return applied;
  }
}

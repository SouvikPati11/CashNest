import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_result.dart';
import '../data/rewards_repository.dart';
import '../models/claim_result.dart';
import '../models/scratch_card.dart';

/// Loads available scratch cards and drives the reveal → claim flow.
class ScratchController extends StateNotifier<AsyncValue<List<ScratchCard>>> {
  ScratchController(this._repository) : super(const AsyncValue.loading());

  final RewardsRepository _repository;

  Future<void> load() async {
    state = const AsyncValue.loading();
    state = await AsyncValue.guard(_fetch);
  }

  Future<void> refresh() async {
    state = await AsyncValue.guard(_fetch);
  }

  Future<List<ScratchCard>> _fetch() async {
    final result = await _repository.fetchAvailableScratchCards();
    return switch (result) {
      ApiSuccess(:final data) => data,
      ApiFailure(:final error) => throw error,
    };
  }

  /// Reveal a card; returns the revealed card (with reward) or throws.
  Future<ScratchCard> reveal(String uuid) async {
    final result = await _repository.revealScratchCard(uuid);
    return switch (result) {
      ApiSuccess(:final data) => _replace(data),
      ApiFailure(:final error) => throw error,
    };
  }

  /// Claim a revealed card; returns the [ClaimResult] or throws. The claimed
  /// card is removed from the available list.
  Future<ClaimResult> claim(String uuid) async {
    final result = await _repository.claimScratchCard(uuid);
    return switch (result) {
      ApiSuccess(:final data) => _remove(uuid, data),
      ApiFailure(:final error) => throw error,
    };
  }

  ScratchCard _replace(ScratchCard card) {
    final current = state.valueOrNull;
    if (current != null) {
      state = AsyncValue.data([
        for (final c in current) if (c.uuid == card.uuid) card else c,
      ]);
    }
    return card;
  }

  ClaimResult _remove(String uuid, ClaimResult result) {
    final current = state.valueOrNull;
    if (current != null) {
      state = AsyncValue.data(current.where((c) => c.uuid != uuid).toList());
    }
    return result;
  }
}

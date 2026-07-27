import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../data/rewards_repository.dart';
import '../models/reward_history_entry.dart';

/// Merges the scratch, spin, and task history endpoints into one list sorted by
/// recency. Each source is best-effort: a single failure does not blank the
/// whole screen.
class RewardHistoryController extends StateNotifier<AsyncValue<List<RewardHistoryEntry>>> {
  RewardHistoryController(this._repository) : super(const AsyncValue.loading());

  final RewardsRepository _repository;

  Future<void> load() async {
    state = const AsyncValue.loading();
    state = await AsyncValue.guard(_fetch);
  }

  Future<void> refresh() async {
    state = await AsyncValue.guard(_fetch);
  }

  Future<List<RewardHistoryEntry>> _fetch() async {
    final results = await Future.wait([
      _repository.fetchScratchHistory(),
      _repository.fetchSpinHistory(),
      _repository.fetchTaskHistory(),
    ]);

    final merged = <RewardHistoryEntry>[
      for (final result in results) ...(result.dataOrNull ?? const []),
    ]..sort((a, b) {
        final da = a.createdAt;
        final db = b.createdAt;
        if (da == null && db == null) {
          return 0;
        }
        if (da == null) {
          return 1;
        }
        if (db == null) {
          return -1;
        }
        return db.compareTo(da);
      });

    return merged;
  }
}

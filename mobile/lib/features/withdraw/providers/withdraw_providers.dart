import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../app/di/providers.dart';
import '../../offerwall/application/paginated_list_state.dart';
import '../application/withdraw_form_controller.dart';
import '../application/withdraw_form_state.dart';
import '../application/withdraw_history_controller.dart';
import '../data/withdraw_repository.dart';
import '../data/withdraw_repository_impl.dart';
import '../models/withdraw_detail.dart';
import '../models/withdraw_request.dart';

/// Riverpod wiring for the withdraw feature. Reuses the foundation API client
/// and the shared paginated infrastructure; no completed module is modified.

final withdrawRepositoryProvider = Provider<WithdrawRepository>(
  (ref) => WithdrawRepositoryImpl(ref.watch(apiClientProvider)),
);

final withdrawFormControllerProvider =
    StateNotifierProvider<WithdrawFormController, WithdrawFormState>(
  (ref) => WithdrawFormController(ref.watch(withdrawRepositoryProvider)),
);

final withdrawHistoryControllerProvider =
    StateNotifierProvider<WithdrawHistoryController, PaginatedListState<WithdrawRequest>>(
  (ref) => WithdrawHistoryController(ref.watch(withdrawRepositoryProvider)),
);

/// Single withdrawal detail + status timeline, by uuid.
final withdrawDetailProvider = FutureProvider.family<WithdrawDetail, String>((ref, uuid) async {
  final result = await ref.watch(withdrawRepositoryProvider).fetchDetail(uuid);
  return result.when(success: (data) => data, failure: (error) => throw error);
});

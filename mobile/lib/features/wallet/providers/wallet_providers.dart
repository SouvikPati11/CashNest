import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../app/di/providers.dart';
import '../application/transactions_controller.dart';
import '../application/transactions_state.dart';
import '../application/wallet_summary_controller.dart';
import '../data/wallet_repository.dart';
import '../data/wallet_repository_impl.dart';
import '../models/wallet_overview.dart';
import '../models/wallet_transaction.dart';

/// Riverpod wiring for the wallet module. Reuses the foundation API client; the
/// foundation, auth, and home modules are not modified.

final walletRepositoryProvider = Provider<WalletRepository>(
  (ref) => WalletRepositoryImpl(ref.watch(apiClientProvider)),
);

final walletSummaryControllerProvider =
    StateNotifierProvider<WalletSummaryController, AsyncValue<WalletOverview>>(
  (ref) => WalletSummaryController(ref.watch(walletRepositoryProvider)),
);

final transactionsControllerProvider =
    StateNotifierProvider<TransactionsController, TransactionsState>(
  (ref) => TransactionsController(ref.watch(walletRepositoryProvider)),
);

/// Single transaction detail (metadata + related transaction), by uuid.
final transactionDetailProvider =
    FutureProvider.family<WalletTransaction, String>((ref, uuid) async {
  final result = await ref.watch(walletRepositoryProvider).fetchTransaction(uuid);
  return result.when(
    success: (data) => data,
    failure: (error) => throw error,
  );
});

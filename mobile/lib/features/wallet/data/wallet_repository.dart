import '../../../core/network/api_result.dart';
import '../models/conversion_rate.dart';
import '../models/transaction_filter.dart';
import '../models/transaction_page.dart';
import '../models/wallet_summary.dart';
import '../models/wallet_transaction.dart';

/// Contract for wallet reads: balance summary, conversion rate, and the
/// paginated/filterable transaction ledger.
abstract interface class WalletRepository {
  Future<ApiResult<WalletSummary>> fetchSummary();

  Future<ApiResult<ConversionRate>> fetchConversion();

  Future<ApiResult<TransactionPage>> fetchTransactions({
    TransactionFilter filter = TransactionFilter.none,
    String? cursor,
    int limit = 20,
  });

  Future<ApiResult<WalletTransaction>> fetchTransaction(String uuid);
}

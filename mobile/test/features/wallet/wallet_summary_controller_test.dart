import 'package:cashnest/core/error/app_exception.dart';
import 'package:cashnest/core/network/api_result.dart';
import 'package:cashnest/features/wallet/application/wallet_summary_controller.dart';
import 'package:cashnest/features/wallet/data/wallet_repository.dart';
import 'package:cashnest/features/wallet/models/conversion_rate.dart';
import 'package:cashnest/features/wallet/models/transaction_filter.dart';
import 'package:cashnest/features/wallet/models/transaction_page.dart';
import 'package:cashnest/features/wallet/models/wallet_summary.dart';
import 'package:cashnest/features/wallet/models/wallet_transaction.dart';
import 'package:flutter_test/flutter_test.dart';

const _summary = WalletSummary(
  coinBalance: 4200,
  coinReserved: 0,
  available: 4200,
  cashBalance: '4.2000',
  currency: 'INR',
  lifetimeEarned: 12000,
  lifetimeSpent: 7800,
);

const _conversion = ConversionRate(
  coinToCashRate: '0.001',
  currency: 'INR',
  minWithdrawCoins: 5000,
  maxWithdrawCoins: 100000,
);

class FakeWalletRepository implements WalletRepository {
  ApiResult<WalletSummary> summary = const ApiResult.success(_summary);
  ApiResult<ConversionRate> conversion = const ApiResult.success(_conversion);

  @override
  Future<ApiResult<WalletSummary>> fetchSummary() async => summary;

  @override
  Future<ApiResult<ConversionRate>> fetchConversion() async => conversion;

  @override
  Future<ApiResult<TransactionPage>> fetchTransactions({
    TransactionFilter filter = TransactionFilter.none,
    String? cursor,
    int limit = 20,
  }) async =>
      const ApiResult.success(TransactionPage.empty);

  @override
  Future<ApiResult<WalletTransaction>> fetchTransaction(String uuid) async =>
      const ApiResult.success(WalletTransaction(uuid: 'x', direction: 'credit', amount: 1));
}

void main() {
  late FakeWalletRepository repo;

  setUp(() => repo = FakeWalletRepository());

  test('load aggregates summary and conversion', () async {
    final controller = WalletSummaryController(repo);
    await controller.load();

    final overview = controller.debugState.valueOrNull;
    expect(overview, isNotNull);
    expect(overview!.summary.coinBalance, 4200);
    expect(overview.conversion, isNotNull);
    expect(overview.conversion!.minWithdrawCoins, 5000);
    controller.dispose();
  });

  test('required summary failure surfaces an error state', () async {
    repo.summary = const ApiResult.failure(ServerException('boom', statusCode: 500));
    final controller = WalletSummaryController(repo);
    await controller.load();

    expect(controller.debugState, isA<AsyncError<dynamic>>());
    controller.dispose();
  });

  test('conversion failure degrades to null without erroring', () async {
    repo.conversion = const ApiResult.failure(ServerException('no rate', statusCode: 500));
    final controller = WalletSummaryController(repo);
    await controller.load();

    final overview = controller.debugState.valueOrNull;
    expect(overview, isNotNull);
    expect(overview!.summary.coinBalance, 4200);
    expect(overview.conversion, isNull);
    controller.dispose();
  });
}

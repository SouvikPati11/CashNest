import 'package:cashnest/core/error/app_exception.dart';
import 'package:cashnest/core/network/api_result.dart';
import 'package:cashnest/features/wallet/application/transactions_controller.dart';
import 'package:cashnest/features/wallet/application/transactions_state.dart';
import 'package:cashnest/features/wallet/data/wallet_repository.dart';
import 'package:cashnest/features/wallet/models/conversion_rate.dart';
import 'package:cashnest/features/wallet/models/transaction_filter.dart';
import 'package:cashnest/features/wallet/models/transaction_page.dart';
import 'package:cashnest/features/wallet/models/wallet_summary.dart';
import 'package:cashnest/features/wallet/models/wallet_transaction.dart';
import 'package:flutter_test/flutter_test.dart';

WalletTransaction _tx(String uuid, {String direction = 'credit', String type = 'offerwall', String? description}) {
  return WalletTransaction(
    uuid: uuid,
    direction: direction,
    amount: 100,
    type: type,
    description: description,
  );
}

/// A repository whose transaction pages are scripted per call.
class ScriptedWalletRepository implements WalletRepository {
  ScriptedWalletRepository(this._pages);

  final List<ApiResult<TransactionPage>> _pages;
  int calls = 0;
  TransactionFilter? lastFilter;
  String? lastCursor;

  @override
  Future<ApiResult<TransactionPage>> fetchTransactions({
    TransactionFilter filter = TransactionFilter.none,
    String? cursor,
    int limit = 20,
  }) async {
    lastFilter = filter;
    lastCursor = cursor;
    final page = _pages[calls.clamp(0, _pages.length - 1)];
    calls++;
    return page;
  }

  @override
  Future<ApiResult<WalletSummary>> fetchSummary() async =>
      const ApiResult.success(WalletSummary.empty);

  @override
  Future<ApiResult<ConversionRate>> fetchConversion() async =>
      const ApiResult.failure(ServerException('n/a', statusCode: 500));

  @override
  Future<ApiResult<WalletTransaction>> fetchTransaction(String uuid) async =>
      ApiResult.success(_tx(uuid));
}

void main() {
  test('load populates the first page and cursor', () async {
    final repo = ScriptedWalletRepository([
      ApiResult.success(TransactionPage(items: [_tx('a'), _tx('b')], nextCursor: 'c1', hasMore: true)),
    ]);
    final controller = TransactionsController(repo);
    await controller.load();

    expect(controller.debugState.status, TransactionsStatus.ready);
    expect(controller.debugState.items, hasLength(2));
    expect(controller.debugState.hasMore, isTrue);
    expect(controller.debugState.nextCursor, 'c1');
    controller.dispose();
  });

  test('loadMore appends the next page and updates the cursor', () async {
    final repo = ScriptedWalletRepository([
      ApiResult.success(TransactionPage(items: [_tx('a')], nextCursor: 'c1', hasMore: true)),
      ApiResult.success(TransactionPage(items: [_tx('b')], hasMore: false)),
    ]);
    final controller = TransactionsController(repo);
    await controller.load();
    await controller.loadMore();

    expect(controller.debugState.items.map((t) => t.uuid), ['a', 'b']);
    expect(controller.debugState.hasMore, isFalse);
    expect(controller.debugState.nextCursor, isNull);
    expect(repo.lastCursor, 'c1');
    controller.dispose();
  });

  test('loadMore is a no-op when there is no next page', () async {
    final repo = ScriptedWalletRepository([
      ApiResult.success(TransactionPage(items: [_tx('a')], hasMore: false)),
    ]);
    final controller = TransactionsController(repo);
    await controller.load();
    await controller.loadMore();

    expect(repo.calls, 1);
    controller.dispose();
  });

  test('first-page failure surfaces the error state', () async {
    final repo = ScriptedWalletRepository([
      const ApiResult.failure(ServerException('boom', statusCode: 500)),
    ]);
    final controller = TransactionsController(repo);
    await controller.load();

    expect(controller.debugState.status, TransactionsStatus.error);
    expect(controller.debugState.error, isNotNull);
    controller.dispose();
  });

  test('load-more failure sets an inline error but keeps items', () async {
    final repo = ScriptedWalletRepository([
      ApiResult.success(TransactionPage(items: [_tx('a')], nextCursor: 'c1', hasMore: true)),
      const ApiResult.failure(NetworkException('offline')),
    ]);
    final controller = TransactionsController(repo);
    await controller.load();
    await controller.loadMore();

    expect(controller.debugState.items, hasLength(1));
    expect(controller.debugState.loadMoreError, isNotNull);
    expect(controller.debugState.isLoadingMore, isFalse);
    controller.dispose();
  });

  test('applyFilter reloads from the first page with the new filter', () async {
    final repo = ScriptedWalletRepository([
      ApiResult.success(TransactionPage(items: [_tx('a')], hasMore: false)),
      ApiResult.success(TransactionPage(items: [_tx('b', direction: 'debit')], hasMore: false)),
    ]);
    final controller = TransactionsController(repo);
    await controller.load();
    await controller.applyFilter(const TransactionFilter(direction: 'debit'));

    expect(controller.debugState.filter.direction, 'debit');
    expect(controller.debugState.items.single.uuid, 'b');
    expect(repo.lastFilter?.direction, 'debit');
    controller.dispose();
  });

  test('setSearch filters loaded items client-side', () async {
    final repo = ScriptedWalletRepository([
      ApiResult.success(TransactionPage(
        items: [
          _tx('a', description: 'AdGate offer'),
          _tx('b', type: 'checkin', description: 'Daily bonus'),
        ],
        hasMore: false,
      )),
    ]);
    final controller = TransactionsController(repo);
    await controller.load();

    controller.setSearch('daily');
    expect(controller.debugState.visibleItems.single.uuid, 'b');

    controller.setSearch('');
    expect(controller.debugState.visibleItems, hasLength(2));
    controller.dispose();
  });
}

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_result.dart';
import '../data/wallet_repository.dart';
import '../models/transaction_filter.dart';
import '../models/transaction_page.dart';
import 'transactions_state.dart';

/// Drives the transaction history: first-page load, cursor-based infinite
/// scroll, filtering, client-side search, and pull-to-refresh.
class TransactionsController extends StateNotifier<TransactionsState> {
  TransactionsController(this._repository, {this.pageSize = 20})
      : super(const TransactionsState());

  final WalletRepository _repository;
  final int pageSize;

  /// Load (or reload) the first page for the current filter.
  Future<void> load() async {
    state = state.copyWith(
      status: TransactionsStatus.loading,
      resetError: true,
      resetLoadMoreError: true,
    );
    final result = await _repository.fetchTransactions(
      filter: state.filter,
      limit: pageSize,
    );
    _applyFirstPage(result);
  }

  /// Pull-to-refresh: reload the first page without clearing the visible list.
  Future<void> refresh() async {
    state = state.copyWith(isRefreshing: true, resetLoadMoreError: true);
    final result = await _repository.fetchTransactions(
      filter: state.filter,
      limit: pageSize,
    );
    state = state.copyWith(isRefreshing: false);
    _applyFirstPage(result);
  }

  /// Fetch the next page and append it (infinite scroll).
  Future<void> loadMore() async {
    if (state.isLoadingMore || !state.hasMore || state.nextCursor == null) {
      return;
    }
    state = state.copyWith(isLoadingMore: true, resetLoadMoreError: true);
    final result = await _repository.fetchTransactions(
      filter: state.filter,
      cursor: state.nextCursor,
      limit: pageSize,
    );
    switch (result) {
      case ApiSuccess(:final data):
        state = state.copyWith(
          items: [...state.items, ...data.items],
          hasMore: data.hasMore,
          nextCursor: data.nextCursor,
          resetNextCursor: data.nextCursor == null,
          isLoadingMore: false,
        );
      case ApiFailure(:final error):
        state = state.copyWith(isLoadingMore: false, loadMoreError: error);
    }
  }

  /// Retry a failed load-more.
  Future<void> retryLoadMore() => loadMore();

  /// Apply a new filter and reload from the first page.
  Future<void> applyFilter(TransactionFilter filter) async {
    state = state.copyWith(filter: filter);
    await load();
  }

  /// Clear all filters and reload.
  Future<void> clearFilter() => applyFilter(TransactionFilter.none);

  /// Update the client-side search query (no network call).
  void setSearch(String query) {
    state = state.copyWith(searchQuery: query);
  }

  void _applyFirstPage(ApiResult<TransactionPage> result) {
    switch (result) {
      case ApiSuccess(:final data):
        state = state.copyWith(
          status: TransactionsStatus.ready,
          items: data.items,
          hasMore: data.hasMore,
          nextCursor: data.nextCursor,
          resetNextCursor: data.nextCursor == null,
          resetError: true,
        );
      case ApiFailure(:final error):
        // Keep any previously loaded items visible on refresh failures.
        state = state.copyWith(
          status: state.items.isEmpty ? TransactionsStatus.error : TransactionsStatus.ready,
          error: state.items.isEmpty ? error : null,
          resetError: state.items.isNotEmpty,
        );
    }
  }
}

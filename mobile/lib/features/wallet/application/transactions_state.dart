import 'package:flutter/foundation.dart';

import '../../../core/error/app_exception.dart';
import '../models/transaction_filter.dart';
import '../models/wallet_transaction.dart';

/// Loading phase of the transaction list's first page.
enum TransactionsStatus { initial, loading, ready, error }

/// State for the paginated, filterable, searchable transaction history.
@immutable
class TransactionsState {
  const TransactionsState({
    this.status = TransactionsStatus.initial,
    this.items = const [],
    this.filter = TransactionFilter.none,
    this.searchQuery = '',
    this.hasMore = false,
    this.nextCursor,
    this.isLoadingMore = false,
    this.isRefreshing = false,
    this.error,
    this.loadMoreError,
  });

  final TransactionsStatus status;

  /// All loaded transactions (across pages), before search filtering.
  final List<WalletTransaction> items;
  final TransactionFilter filter;
  final String searchQuery;
  final bool hasMore;
  final String? nextCursor;
  final bool isLoadingMore;
  final bool isRefreshing;

  /// First-page error (drives the full error state).
  final AppException? error;

  /// Load-more error (drives an inline retry at the list footer).
  final AppException? loadMoreError;

  bool get isFirstLoad => status == TransactionsStatus.loading;

  bool get hasSearch => searchQuery.trim().isNotEmpty;

  /// Items after applying the client-side search over the loaded pages.
  List<WalletTransaction> get visibleItems {
    if (!hasSearch) {
      return items;
    }
    final q = searchQuery.trim().toLowerCase();
    return items.where((t) {
      final desc = t.description?.toLowerCase() ?? '';
      final type = t.type.toLowerCase();
      final module = t.sourceModule?.toLowerCase() ?? '';
      return desc.contains(q) || type.contains(q) || module.contains(q);
    }).toList(growable: false);
  }

  TransactionsState copyWith({
    TransactionsStatus? status,
    List<WalletTransaction>? items,
    TransactionFilter? filter,
    String? searchQuery,
    bool? hasMore,
    String? nextCursor,
    bool resetNextCursor = false,
    bool? isLoadingMore,
    bool? isRefreshing,
    AppException? error,
    bool resetError = false,
    AppException? loadMoreError,
    bool resetLoadMoreError = false,
  }) {
    return TransactionsState(
      status: status ?? this.status,
      items: items ?? this.items,
      filter: filter ?? this.filter,
      searchQuery: searchQuery ?? this.searchQuery,
      hasMore: hasMore ?? this.hasMore,
      nextCursor: resetNextCursor ? null : (nextCursor ?? this.nextCursor),
      isLoadingMore: isLoadingMore ?? this.isLoadingMore,
      isRefreshing: isRefreshing ?? this.isRefreshing,
      error: resetError ? null : (error ?? this.error),
      loadMoreError: resetLoadMoreError ? null : (loadMoreError ?? this.loadMoreError),
    );
  }
}

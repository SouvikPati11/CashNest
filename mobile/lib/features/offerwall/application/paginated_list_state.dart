import 'package:flutter/foundation.dart';

import '../../../core/error/app_exception.dart';

/// Loading phase of a paginated list's first page.
enum ListStatus { initial, loading, ready, error }

/// Generic state for a paginated, searchable list. Search is client-side over
/// the loaded pages; the concrete controller supplies the match predicate.
@immutable
class PaginatedListState<T> {
  const PaginatedListState({
    this.status = ListStatus.initial,
    this.items = const [],
    this.searchQuery = '',
    this.hasMore = false,
    this.nextCursor,
    this.isLoadingMore = false,
    this.isRefreshing = false,
    this.error,
    this.loadMoreError,
  });

  final ListStatus status;
  final List<T> items;
  final String searchQuery;
  final bool hasMore;
  final String? nextCursor;
  final bool isLoadingMore;
  final bool isRefreshing;
  final AppException? error;
  final AppException? loadMoreError;

  bool get isFirstLoad => status == ListStatus.loading;
  bool get hasSearch => searchQuery.trim().isNotEmpty;

  PaginatedListState<T> copyWith({
    ListStatus? status,
    List<T>? items,
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
    return PaginatedListState<T>(
      status: status ?? this.status,
      items: items ?? this.items,
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

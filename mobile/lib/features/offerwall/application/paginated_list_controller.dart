import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_result.dart';
import '../models/page_result.dart';
import 'paginated_list_state.dart';

/// Reusable base for cursor-paginated lists with pull-to-refresh, infinite
/// scroll, and client-side search. Shared across the offerwall and referral
/// features of this module.
///
/// Subclasses implement [fetchPage] (and optionally [matchesSearch] to enable
/// search). Filter changes are applied by mutating subclass state and calling
/// [reload].
abstract class PaginatedListController<T> extends StateNotifier<PaginatedListState<T>> {
  PaginatedListController({this.pageSize = 20}) : super(PaginatedListState<T>());

  final int pageSize;

  /// Fetch one page from [cursor] (null = first page).
  Future<ApiResult<PageResult<T>>> fetchPage({String? cursor});

  /// Override to enable client-side search; [query] is already lower-cased.
  bool matchesSearch(T item, String query) => true;

  /// Items after applying the current search query.
  List<T> get visibleItems {
    final q = state.searchQuery.trim().toLowerCase();
    if (q.isEmpty) {
      return state.items;
    }
    return state.items.where((e) => matchesSearch(e, q)).toList(growable: false);
  }

  Future<void> load() async {
    state = state.copyWith(
      status: ListStatus.loading,
      resetError: true,
      resetLoadMoreError: true,
    );
    _applyFirstPage(await fetchPage());
  }

  /// Reload the first page keeping the visible list until it resolves.
  Future<void> reload() => load();

  Future<void> refresh() async {
    state = state.copyWith(isRefreshing: true, resetLoadMoreError: true);
    final result = await fetchPage();
    state = state.copyWith(isRefreshing: false);
    _applyFirstPage(result);
  }

  Future<void> loadMore() async {
    if (state.isLoadingMore || !state.hasMore || state.nextCursor == null) {
      return;
    }
    state = state.copyWith(isLoadingMore: true, resetLoadMoreError: true);
    final result = await fetchPage(cursor: state.nextCursor);
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

  Future<void> retryLoadMore() => loadMore();

  void setSearch(String query) {
    state = state.copyWith(searchQuery: query);
  }

  void _applyFirstPage(ApiResult<PageResult<T>> result) {
    switch (result) {
      case ApiSuccess(:final data):
        state = state.copyWith(
          status: ListStatus.ready,
          items: data.items,
          hasMore: data.hasMore,
          nextCursor: data.nextCursor,
          resetNextCursor: data.nextCursor == null,
          resetError: true,
        );
      case ApiFailure(:final error):
        state = state.copyWith(
          status: state.items.isEmpty ? ListStatus.error : ListStatus.ready,
          error: state.items.isEmpty ? error : null,
          resetError: state.items.isNotEmpty,
        );
    }
  }
}

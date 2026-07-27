import 'package:cashnest/core/error/app_exception.dart';
import 'package:cashnest/core/network/api_result.dart';
import 'package:cashnest/features/offerwall/application/paginated_list_controller.dart';
import 'package:cashnest/features/offerwall/application/paginated_list_state.dart';
import 'package:cashnest/features/offerwall/models/page_result.dart';
import 'package:flutter_test/flutter_test.dart';

/// A concrete controller over strings, scripted with a queue of page results.
class _TestController extends PaginatedListController<String> {
  _TestController(this._pages);

  final List<ApiResult<PageResult<String>>> _pages;
  int calls = 0;

  @override
  Future<ApiResult<PageResult<String>>> fetchPage({String? cursor}) async {
    final page = _pages[calls.clamp(0, _pages.length - 1)];
    calls++;
    return page;
  }

  @override
  bool matchesSearch(String item, String query) => item.toLowerCase().contains(query);
}

void main() {
  test('load populates the first page and cursor', () async {
    final c = _TestController([
      const ApiResult.success(PageResult(items: ['a', 'b'], nextCursor: 'c1', hasMore: true)),
    ]);
    await c.load();
    expect(c.debugState.status, ListStatus.ready);
    expect(c.debugState.items, ['a', 'b']);
    expect(c.debugState.hasMore, isTrue);
    expect(c.debugState.nextCursor, 'c1');
    c.dispose();
  });

  test('loadMore appends and updates the cursor', () async {
    final c = _TestController([
      const ApiResult.success(PageResult(items: ['a'], nextCursor: 'c1', hasMore: true)),
      const ApiResult.success(PageResult(items: ['b'], hasMore: false)),
    ]);
    await c.load();
    await c.loadMore();
    expect(c.debugState.items, ['a', 'b']);
    expect(c.debugState.hasMore, isFalse);
    expect(c.debugState.nextCursor, isNull);
    c.dispose();
  });

  test('loadMore is a no-op when there is no next page', () async {
    final c = _TestController([
      const ApiResult.success(PageResult(items: ['a'], hasMore: false)),
    ]);
    await c.load();
    await c.loadMore();
    expect(c.calls, 1);
    c.dispose();
  });

  test('first-page failure surfaces the error state', () async {
    final c = _TestController([
      const ApiResult.failure(ServerException('boom', statusCode: 500)),
    ]);
    await c.load();
    expect(c.debugState.status, ListStatus.error);
    expect(c.debugState.error, isNotNull);
    c.dispose();
  });

  test('load-more failure keeps items and sets inline error', () async {
    final c = _TestController([
      const ApiResult.success(PageResult(items: ['a'], nextCursor: 'c1', hasMore: true)),
      const ApiResult.failure(NetworkException('offline')),
    ]);
    await c.load();
    await c.loadMore();
    expect(c.debugState.items, ['a']);
    expect(c.debugState.loadMoreError, isNotNull);
    expect(c.debugState.isLoadingMore, isFalse);
    c.dispose();
  });

  test('setSearch filters visibleItems client-side', () async {
    final c = _TestController([
      const ApiResult.success(PageResult(items: ['Apple', 'Banana'], hasMore: false)),
    ]);
    await c.load();
    c.setSearch('ban');
    expect(c.visibleItems, ['Banana']);
    c.setSearch('');
    expect(c.visibleItems, ['Apple', 'Banana']);
    c.dispose();
  });
}

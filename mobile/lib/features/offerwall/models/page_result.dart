import 'package:flutter/foundation.dart';

/// One page of results plus cursor pagination metadata (`meta.pagination`).
@immutable
class PageResult<T> {
  const PageResult({required this.items, this.nextCursor, this.hasMore = false});

  final List<T> items;
  final String? nextCursor;
  final bool hasMore;
}

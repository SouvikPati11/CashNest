import 'package:flutter/foundation.dart';

import 'wallet_transaction.dart';

/// One page of transaction history plus cursor pagination metadata
/// (`meta.pagination`).
@immutable
class TransactionPage {
  const TransactionPage({
    required this.items,
    this.nextCursor,
    this.hasMore = false,
  });

  final List<WalletTransaction> items;
  final String? nextCursor;
  final bool hasMore;

  static const TransactionPage empty = TransactionPage(items: []);
}

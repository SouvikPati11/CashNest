import 'package:flutter/foundation.dart';

/// Filters for the transaction history query (`GET /v1/wallet/transactions`).
///
/// Maps to the backend filtering standard (`filter[...]` query params).
@immutable
class TransactionFilter {
  const TransactionFilter({
    this.type,
    this.direction,
    this.dateFrom,
    this.dateTo,
  });

  /// Ledger type (e.g. `offerwall`, `checkin`, `withdraw`); `null` = any.
  final String? type;

  /// `credit` or `debit`; `null` = any.
  final String? direction;
  final DateTime? dateFrom;
  final DateTime? dateTo;

  static const TransactionFilter none = TransactionFilter();

  bool get isActive =>
      type != null || direction != null || dateFrom != null || dateTo != null;

  int get activeCount => [
        if (type != null) 1,
        if (direction != null) 1,
        if (dateFrom != null || dateTo != null) 1,
      ].length;

  /// Backend query params (`filter[...]`), omitting unset fields.
  Map<String, dynamic> toQuery() {
    return <String, dynamic>{
      if (type != null) 'filter[type]': type,
      if (direction != null) 'filter[direction]': direction,
      if (dateFrom != null) 'filter[date_from]': _isoDate(dateFrom!),
      if (dateTo != null) 'filter[date_to]': _isoDate(dateTo!),
    };
  }

  static String _isoDate(DateTime d) {
    final utc = d.toUtc();
    final two = (int n) => n.toString().padLeft(2, '0');
    return '${utc.year}-${two(utc.month)}-${two(utc.day)}';
  }

  /// Rebuild the filter; pass `resetX: true` to clear a nullable field.
  TransactionFilter copyWith({
    String? type,
    bool resetType = false,
    String? direction,
    bool resetDirection = false,
    DateTime? dateFrom,
    bool resetDateFrom = false,
    DateTime? dateTo,
    bool resetDateTo = false,
  }) {
    return TransactionFilter(
      type: resetType ? null : (type ?? this.type),
      direction: resetDirection ? null : (direction ?? this.direction),
      dateFrom: resetDateFrom ? null : (dateFrom ?? this.dateFrom),
      dateTo: resetDateTo ? null : (dateTo ?? this.dateTo),
    );
  }

  @override
  bool operator ==(Object other) =>
      other is TransactionFilter &&
      other.type == type &&
      other.direction == direction &&
      other.dateFrom == dateFrom &&
      other.dateTo == dateTo;

  @override
  int get hashCode => Object.hash(type, direction, dateFrom, dateTo);
}

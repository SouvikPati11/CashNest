import 'package:flutter/foundation.dart';

/// A withdrawal request (`POST /v1/withdraw/request`, `/history`, `/{uuid}`).
@immutable
class WithdrawRequest {
  const WithdrawRequest({
    required this.uuid,
    required this.status,
    required this.coinsAmount,
    required this.cashAmount,
    required this.netAmount,
    this.feeAmount = '0',
    this.currency = 'INR',
    this.conversionRate,
    this.methodCode,
    this.holdTransactionUuid,
    this.createdAt,
  });

  final String uuid;
  final String status;
  final int coinsAmount;
  final String cashAmount;
  final String netAmount;
  final String feeAmount;
  final String currency;
  final String? conversionRate;
  final String? methodCode;
  final String? holdTransactionUuid;
  final DateTime? createdAt;

  bool get isPending => status == 'pending';
  bool get isCancellable => status == 'pending';

  static int _int(dynamic v) => (v as num?)?.toInt() ?? 0;

  static DateTime? _date(dynamic v) => v is String ? DateTime.tryParse(v) : null;

  factory WithdrawRequest.fromJson(Map<String, dynamic> json) {
    return WithdrawRequest(
      uuid: json['uuid'] as String? ?? '',
      status: json['status'] as String? ?? 'pending',
      coinsAmount: _int(json['coins_amount']),
      cashAmount: json['cash_amount']?.toString() ?? '0',
      netAmount: json['net_amount']?.toString() ?? json['cash_amount']?.toString() ?? '0',
      feeAmount: json['fee_amount']?.toString() ?? '0',
      currency: json['currency'] as String? ?? 'INR',
      conversionRate: json['conversion_rate']?.toString(),
      methodCode: json['method_code'] as String?,
      holdTransactionUuid: json['hold_transaction_uuid'] as String?,
      createdAt: _date(json['created_at']),
    );
  }
}

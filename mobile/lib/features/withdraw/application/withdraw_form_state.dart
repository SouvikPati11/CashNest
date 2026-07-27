import 'package:flutter/foundation.dart';

import '../../../core/error/app_exception.dart';
import '../models/conversion_info.dart';
import '../models/withdraw_method.dart';
import '../models/withdraw_quote.dart';

/// Loading phase of the withdraw form's reference data (methods + conversion).
enum WithdrawLoad { loading, ready, error }

/// State for the withdraw form: reference data, the user's selection, the live
/// quote, and submission status.
@immutable
class WithdrawFormState {
  const WithdrawFormState({
    this.loadStatus = WithdrawLoad.loading,
    this.loadError,
    this.methods = const [],
    this.conversion = ConversionInfo.empty,
    this.method,
    this.coins = 0,
    this.paymentDetail = const {},
    this.submitting = false,
    this.submitError,
  });

  final WithdrawLoad loadStatus;
  final AppException? loadError;
  final List<WithdrawMethod> methods;
  final ConversionInfo conversion;
  final WithdrawMethod? method;
  final int coins;
  final Map<String, String> paymentDetail;
  final bool submitting;
  final AppException? submitError;

  /// The live coin→cash quote (cash, fee, net) for the current input.
  WithdrawQuote get quote {
    final m = method;
    if (m == null || coins <= 0) {
      return WithdrawQuote(coins: coins, cash: 0, fee: 0, net: 0, currency: conversion.currency);
    }
    return WithdrawQuote.compute(
      coins: coins,
      rate: conversion.rate,
      feeFraction: m.feeFraction,
      currency: conversion.currency,
    );
  }

  bool get allDetailsFilled {
    final m = method;
    if (m == null) {
      return false;
    }
    return m.detailFields.every((f) => (paymentDetail[f] ?? '').trim().isNotEmpty);
  }

  /// Whether the form is client-side valid to submit (server enforces balance).
  bool get canSubmit {
    final m = method;
    if (m == null || submitting) {
      return false;
    }
    return coins >= m.minCoins && coins <= m.maxCoins && allDetailsFilled;
  }

  WithdrawFormState copyWith({
    WithdrawLoad? loadStatus,
    AppException? loadError,
    bool resetLoadError = false,
    List<WithdrawMethod>? methods,
    ConversionInfo? conversion,
    WithdrawMethod? method,
    int? coins,
    Map<String, String>? paymentDetail,
    bool? submitting,
    AppException? submitError,
    bool resetSubmitError = false,
  }) {
    return WithdrawFormState(
      loadStatus: loadStatus ?? this.loadStatus,
      loadError: resetLoadError ? null : (loadError ?? this.loadError),
      methods: methods ?? this.methods,
      conversion: conversion ?? this.conversion,
      method: method ?? this.method,
      coins: coins ?? this.coins,
      paymentDetail: paymentDetail ?? this.paymentDetail,
      submitting: submitting ?? this.submitting,
      submitError: resetSubmitError ? null : (submitError ?? this.submitError),
    );
  }
}

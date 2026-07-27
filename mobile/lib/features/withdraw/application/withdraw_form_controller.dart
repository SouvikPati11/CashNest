import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/error/app_exception.dart';
import '../../../core/network/api_result.dart';
import '../data/withdraw_repository.dart';
import '../models/withdraw_method.dart';
import '../models/withdraw_request.dart';
import 'withdraw_form_state.dart';

/// Loads withdraw methods + conversion, tracks the form input (driving the live
/// quote), and submits the request.
class WithdrawFormController extends StateNotifier<WithdrawFormState> {
  WithdrawFormController(this._repository) : super(const WithdrawFormState());

  final WithdrawRepository _repository;

  Future<void> load() async {
    state = state.copyWith(loadStatus: WithdrawLoad.loading, resetLoadError: true);

    final methodsResult = await _repository.fetchMethods();
    if (methodsResult case ApiFailure(:final error)) {
      state = state.copyWith(loadStatus: WithdrawLoad.error, loadError: error);
      return;
    }
    final methods = methodsResult.dataOrNull ?? const <WithdrawMethod>[];

    // Conversion is best-effort; the server recomputes on submit.
    final conversion = (await _repository.fetchConversion()).dataOrNull ?? state.conversion;

    state = state.copyWith(
      loadStatus: WithdrawLoad.ready,
      methods: methods,
      conversion: conversion,
      method: state.method ?? (methods.isNotEmpty ? methods.first : null),
    );
  }

  Future<void> refresh() => load();

  void selectMethod(WithdrawMethod method) {
    // Reset payment detail when switching methods (schema differs).
    state = state.copyWith(method: method, paymentDetail: const {}, resetSubmitError: true);
  }

  void setCoins(int coins) {
    state = state.copyWith(coins: coins, resetSubmitError: true);
  }

  void setDetail(String field, String value) {
    final updated = Map<String, String>.from(state.paymentDetail)..[field] = value;
    state = state.copyWith(paymentDetail: updated, resetSubmitError: true);
  }

  /// Submit the withdrawal. Returns the created [WithdrawRequest] or throws the
  /// [AppException] on failure.
  Future<WithdrawRequest> submit() async {
    final method = state.method;
    if (method == null) {
      throw const ValidationException('Select a withdrawal method.');
    }
    state = state.copyWith(submitting: true, resetSubmitError: true);
    final result = await _repository.createRequest(
      methodCode: method.code,
      coinsAmount: state.coins,
      paymentDetail: state.paymentDetail,
    );
    switch (result) {
      case ApiSuccess(:final data):
        state = state.copyWith(submitting: false);
        return data;
      case ApiFailure(:final error):
        state = state.copyWith(submitting: false, submitError: error);
        throw error;
    }
  }
}

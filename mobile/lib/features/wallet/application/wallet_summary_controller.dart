import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_result.dart';
import '../data/wallet_repository.dart';
import '../models/wallet_overview.dart';

/// Loads the wallet header: the required balance summary plus the best-effort
/// conversion rate (which degrades to null on failure so the balance still
/// renders).
class WalletSummaryController extends StateNotifier<AsyncValue<WalletOverview>> {
  WalletSummaryController(this._repository) : super(const AsyncValue.loading());

  final WalletRepository _repository;

  Future<void> load() async {
    state = const AsyncValue.loading();
    state = await AsyncValue.guard(_fetch);
  }

  /// Re-fetch without blanking the current header (used for pull-to-refresh).
  Future<void> refresh() async {
    state = await AsyncValue.guard(_fetch);
  }

  Future<WalletOverview> _fetch() async {
    final summaryResult = await _repository.fetchSummary();
    final summary = switch (summaryResult) {
      ApiSuccess(:final data) => data,
      ApiFailure(:final error) => throw error,
    };

    final conversion = (await _repository.fetchConversion()).dataOrNull;
    return WalletOverview(summary: summary, conversion: conversion);
  }
}

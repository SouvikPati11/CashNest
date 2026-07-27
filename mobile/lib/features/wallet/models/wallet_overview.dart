import 'package:flutter/foundation.dart';

import 'conversion_rate.dart';
import 'wallet_summary.dart';

/// The wallet header data: the required balance [summary] plus the best-effort
/// [conversion] rate (null when the conversion endpoint is unavailable).
@immutable
class WalletOverview {
  const WalletOverview({required this.summary, this.conversion});

  final WalletSummary summary;
  final ConversionRate? conversion;

  WalletOverview copyWith({WalletSummary? summary, ConversionRate? conversion}) {
    return WalletOverview(
      summary: summary ?? this.summary,
      conversion: conversion ?? this.conversion,
    );
  }
}

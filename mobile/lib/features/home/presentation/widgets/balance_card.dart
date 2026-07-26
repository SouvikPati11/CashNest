import 'package:flutter/material.dart';

import '../../../../core/theme/app_radius.dart';
import '../../../../core/theme/app_spacing.dart';
import '../../../../shared/extensions/context_extensions.dart';
import '../../l10n/home_strings.dart';
import '../../models/wallet_balance.dart';

/// Prominent balance card showing coins, available/reserved, and cash value.
class BalanceCard extends StatelessWidget {
  const BalanceCard({required this.balance, super.key});

  final WalletBalance balance;

  @override
  Widget build(BuildContext context) {
    final s = HomeStrings.of(context);
    final scheme = context.colors;

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(AppSpacing.xl),
      decoration: BoxDecoration(
        borderRadius: AppRadius.lgAll,
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [scheme.primary, scheme.primaryContainer],
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            s.balanceTitle,
            style: context.textTheme.bodyMedium?.copyWith(color: scheme.onPrimary),
          ),
          const SizedBox(height: AppSpacing.sm),
          Row(
            crossAxisAlignment: CrossAxisAlignment.baseline,
            textBaseline: TextBaseline.alphabetic,
            children: [
              Text(
                _formatCoins(balance.coinBalance),
                style: context.textTheme.displaySmall?.copyWith(
                  color: scheme.onPrimary,
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(width: AppSpacing.sm),
              Text(s.coins, style: context.textTheme.titleMedium?.copyWith(color: scheme.onPrimary)),
            ],
          ),
          const SizedBox(height: AppSpacing.xs),
          Text(
            '≈ ${balance.currency} ${balance.cashBalance}',
            style: context.textTheme.bodyMedium?.copyWith(color: scheme.onPrimary),
          ),
          const SizedBox(height: AppSpacing.lg),
          Row(
            children: [
              _Metric(label: s.availableLabel, value: _formatCoins(balance.available)),
              const SizedBox(width: AppSpacing.xl),
              _Metric(label: s.reservedLabel, value: _formatCoins(balance.coinReserved)),
            ],
          ),
        ],
      ),
    );
  }

  String _formatCoins(int value) {
    final digits = value.toString();
    final buffer = StringBuffer();
    for (var i = 0; i < digits.length; i++) {
      if (i > 0 && (digits.length - i) % 3 == 0) {
        buffer.write(',');
      }
      buffer.write(digits[i]);
    }
    return buffer.toString();
  }
}

class _Metric extends StatelessWidget {
  const _Metric({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    final onPrimary = context.colors.onPrimary;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: context.textTheme.bodySmall?.copyWith(color: onPrimary.withValues(alpha: 0.85)),
        ),
        Text(
          value,
          style: context.textTheme.titleMedium?.copyWith(color: onPrimary, fontWeight: FontWeight.w600),
        ),
      ],
    );
  }
}

import 'package:flutter/material.dart';

import '../../../../core/theme/app_radius.dart';
import '../../../../core/theme/app_spacing.dart';
import '../../../../shared/extensions/context_extensions.dart';
import '../../l10n/wallet_strings.dart';
import '../../models/wallet_summary.dart';
import '../../resources/wallet_formatters.dart';

/// Prominent balance summary: coin balance, cash value, and available/reserved
/// plus lifetime earned/spent.
class BalanceSummaryCard extends StatelessWidget {
  const BalanceSummaryCard({required this.summary, super.key});

  final WalletSummary summary;

  @override
  Widget build(BuildContext context) {
    final s = WalletStrings.of(context);
    final scheme = context.colors;
    final localeCode = Localizations.localeOf(context).languageCode;
    final onPrimary = scheme.onPrimary;

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
            style: context.textTheme.bodyMedium?.copyWith(color: onPrimary),
          ),
          const SizedBox(height: AppSpacing.sm),
          Row(
            crossAxisAlignment: CrossAxisAlignment.baseline,
            textBaseline: TextBaseline.alphabetic,
            children: [
              Flexible(
                child: Text(
                  WalletFormatters.coins(summary.coinBalance, localeCode: localeCode),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: context.textTheme.displaySmall?.copyWith(
                    color: onPrimary,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
              const SizedBox(width: AppSpacing.sm),
              Text(
                s.coins,
                style: context.textTheme.titleMedium?.copyWith(color: onPrimary),
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.xs),
          Text(
            '≈ ${WalletFormatters.cash(summary.cashBalance, summary.currency)}',
            style: context.textTheme.bodyMedium?.copyWith(color: onPrimary),
          ),
          const SizedBox(height: AppSpacing.lg),
          Divider(color: onPrimary.withValues(alpha: 0.24)),
          const SizedBox(height: AppSpacing.md),
          Row(
            children: [
              _Metric(
                label: s.available,
                value: WalletFormatters.coins(summary.available, localeCode: localeCode),
              ),
              const SizedBox(width: AppSpacing.xl),
              _Metric(
                label: s.reserved,
                value: WalletFormatters.coins(summary.coinReserved, localeCode: localeCode),
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.md),
          Row(
            children: [
              _Metric(
                label: s.lifetimeEarned,
                value: WalletFormatters.coins(summary.lifetimeEarned, localeCode: localeCode),
              ),
              const SizedBox(width: AppSpacing.xl),
              _Metric(
                label: s.lifetimeSpent,
                value: WalletFormatters.coins(summary.lifetimeSpent, localeCode: localeCode),
              ),
            ],
          ),
        ],
      ),
    );
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
          style: context.textTheme.titleMedium
              ?.copyWith(color: onPrimary, fontWeight: FontWeight.w600),
        ),
      ],
    );
  }
}

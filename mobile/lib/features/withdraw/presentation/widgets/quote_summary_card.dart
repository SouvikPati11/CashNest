import 'package:flutter/material.dart';

import '../../../../core/theme/app_spacing.dart';
import '../../../../shared/extensions/context_extensions.dart';
import '../../l10n/withdraw_strings.dart';
import '../../models/withdraw_quote.dart';
import '../../resources/withdraw_formatters.dart';

/// A live coin→cash / fee / net summary for the current withdrawal input.
class QuoteSummaryCard extends StatelessWidget {
  const QuoteSummaryCard({required this.quote, super.key});

  final WithdrawQuote quote;

  @override
  Widget build(BuildContext context) {
    final s = WithdrawStrings.of(context);
    final localeCode = Localizations.localeOf(context).languageCode;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.lg),
        child: Column(
          children: [
            _Row(
              label: '${s.cashValue} (${WithdrawFormatters.coins(quote.coins, localeCode: localeCode)} ${s.coins})',
              value: WithdrawFormatters.cashValue(quote.cash, quote.currency),
            ),
            const SizedBox(height: AppSpacing.sm),
            _Row(
              label: s.fee,
              value: '- ${WithdrawFormatters.cashValue(quote.fee, quote.currency)}',
            ),
            const Divider(height: AppSpacing.xl),
            _Row(
              label: s.youReceive,
              value: WithdrawFormatters.cashValue(quote.net, quote.currency),
              emphasize: true,
            ),
          ],
        ),
      ),
    );
  }
}

class _Row extends StatelessWidget {
  const _Row({required this.label, required this.value, this.emphasize = false});

  final String label;
  final String value;
  final bool emphasize;

  @override
  Widget build(BuildContext context) {
    final labelStyle = emphasize
        ? context.textTheme.titleMedium
        : context.textTheme.bodyMedium?.copyWith(color: context.colors.onSurfaceVariant);
    final valueStyle = emphasize
        ? context.textTheme.titleMedium?.copyWith(color: context.colors.primary, fontWeight: FontWeight.w800)
        : context.textTheme.bodyMedium;

    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Flexible(child: Text(label, style: labelStyle)),
        const SizedBox(width: AppSpacing.md),
        Text(value, style: valueStyle),
      ],
    );
  }
}

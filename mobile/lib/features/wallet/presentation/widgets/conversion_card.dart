import 'package:flutter/material.dart';

import '../../../../core/theme/app_radius.dart';
import '../../../../core/theme/app_spacing.dart';
import '../../../../shared/extensions/context_extensions.dart';
import '../../l10n/wallet_strings.dart';
import '../../models/conversion_rate.dart';
import '../../resources/wallet_formatters.dart';

/// Shows the coin→cash conversion rate and withdrawal thresholds.
class ConversionCard extends StatelessWidget {
  const ConversionCard({required this.conversion, super.key});

  final ConversionRate conversion;

  @override
  Widget build(BuildContext context) {
    final s = WalletStrings.of(context);
    final localeCode = Localizations.localeOf(context).languageCode;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.lg),
        child: Row(
          children: [
            Container(
              width: 44,
              height: 44,
              decoration: BoxDecoration(
                color: context.colors.tertiaryContainer,
                borderRadius: AppRadius.mdAll,
              ),
              child: Icon(Icons.swap_horiz, color: context.colors.onTertiaryContainer),
            ),
            const SizedBox(width: AppSpacing.md),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(s.conversionTitle, style: context.textTheme.titleMedium),
                  const SizedBox(height: AppSpacing.xxs),
                  Text(
                    '${WalletFormatters.rate(conversion.coinToCashRate, conversion.currency)} ${s.perCoin}',
                    style: context.textTheme.bodySmall
                        ?.copyWith(color: context.colors.onSurfaceVariant),
                  ),
                  const SizedBox(height: AppSpacing.xs),
                  Wrap(
                    spacing: AppSpacing.md,
                    runSpacing: AppSpacing.xs,
                    children: [
                      _Chip(
                        label: s.minWithdraw,
                        value: WalletFormatters.coins(conversion.minWithdrawCoins, localeCode: localeCode),
                      ),
                      _Chip(
                        label: s.maxWithdraw,
                        value: WalletFormatters.coins(conversion.maxWithdrawCoins, localeCode: localeCode),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _Chip extends StatelessWidget {
  const _Chip({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Text.rich(
      TextSpan(
        children: [
          TextSpan(
            text: '$label: ',
            style: context.textTheme.labelSmall
                ?.copyWith(color: context.colors.onSurfaceVariant),
          ),
          TextSpan(
            text: value,
            style: context.textTheme.labelMedium?.copyWith(fontWeight: FontWeight.w600),
          ),
        ],
      ),
    );
  }
}

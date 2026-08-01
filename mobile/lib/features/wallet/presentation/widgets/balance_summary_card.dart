import 'package:flutter/material.dart';

import '../../../../core/theme/app_gradients.dart';
import '../../../../core/theme/app_radius.dart';
import '../../../../core/theme/app_spacing.dart';
import '../../../../shared/extensions/context_extensions.dart';
import '../../l10n/wallet_strings.dart';
import '../../models/wallet_summary.dart';
import '../../resources/wallet_formatters.dart';

/// Prominent coin-balance card: coin balance, cash value, available/reserved and
/// lifetime earned/spent on an emerald "coins" gradient, with an optional Redeem
/// call-to-action.
class BalanceSummaryCard extends StatelessWidget {
  const BalanceSummaryCard({required this.summary, this.onRedeem, super.key});

  final WalletSummary summary;
  final VoidCallback? onRedeem;

  @override
  Widget build(BuildContext context) {
    final s = WalletStrings.of(context);
    final localeCode = Localizations.localeOf(context).languageCode;
    const onGradient = Colors.white;

    return DecoratedBox(
      decoration: BoxDecoration(
        borderRadius: AppRadius.xlAll,
        boxShadow: AppGradients.glow(context.colors.secondary, opacity: 0.4),
      ),
      child: ClipRRect(
        borderRadius: AppRadius.xlAll,
        child: Stack(
          children: [
            const Positioned.fill(
              child: DecoratedBox(decoration: BoxDecoration(gradient: AppGradients.coins)),
            ),
            Positioned(
              top: -40,
              right: -24,
              child: _Bloom(size: 150, color: Colors.white.withValues(alpha: 0.14)),
            ),
            Padding(
              padding: const EdgeInsets.all(AppSpacing.xl),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.20),
                          borderRadius: AppRadius.smAll,
                        ),
                        child: const Icon(Icons.monetization_on_rounded, color: onGradient, size: 18),
                      ),
                      const SizedBox(width: AppSpacing.sm),
                      Text(
                        s.balanceTitle,
                        style: context.textTheme.labelLarge?.copyWith(
                          color: onGradient.withValues(alpha: 0.92),
                          letterSpacing: 0.3,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: AppSpacing.lg),
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
                            color: onGradient,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                      ),
                      const SizedBox(width: AppSpacing.sm),
                      Text(
                        s.coins,
                        style: context.textTheme.titleMedium?.copyWith(color: onGradient),
                      ),
                    ],
                  ),
                  const SizedBox(height: AppSpacing.xs),
                  Text(
                    '≈ ${WalletFormatters.cash(summary.cashBalance, summary.currency)}',
                    style: context.textTheme.bodyMedium?.copyWith(
                      color: onGradient.withValues(alpha: 0.95),
                    ),
                  ),
                  const SizedBox(height: AppSpacing.lg),
                  Divider(color: onGradient.withValues(alpha: 0.24)),
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
                  if (onRedeem != null) ...[
                    const SizedBox(height: AppSpacing.lg),
                    SizedBox(
                      width: double.infinity,
                      child: FilledButton.icon(
                        onPressed: onRedeem,
                        style: FilledButton.styleFrom(
                          backgroundColor: Colors.white,
                          foregroundColor: const Color(0xFF08453A),
                        ),
                        icon: const Icon(Icons.redeem_rounded, size: 18),
                        label: const Text('Redeem'),
                      ),
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _Bloom extends StatelessWidget {
  const _Bloom({required this.size, required this.color});

  final double size;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(shape: BoxShape.circle, color: color),
    );
  }
}

class _Metric extends StatelessWidget {
  const _Metric({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    const onGradient = Colors.white;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: context.textTheme.bodySmall?.copyWith(color: onGradient.withValues(alpha: 0.85)),
        ),
        Text(
          value,
          style: context.textTheme.titleMedium
              ?.copyWith(color: onGradient, fontWeight: FontWeight.w600),
        ),
      ],
    );
  }
}

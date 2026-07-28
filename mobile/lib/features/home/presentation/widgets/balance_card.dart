import 'package:flutter/material.dart';

import '../../../../core/theme/app_gradients.dart';
import '../../../../core/theme/app_radius.dart';
import '../../../../core/theme/app_spacing.dart';
import '../../../../shared/extensions/context_extensions.dart';
import '../../l10n/home_strings.dart';
import '../../models/wallet_balance.dart';

/// Prominent balance hero showing coins, available/reserved and cash value on a
/// glowing brand gradient with layered decorative depth.
class BalanceCard extends StatelessWidget {
  const BalanceCard({required this.balance, super.key});

  final WalletBalance balance;

  @override
  Widget build(BuildContext context) {
    final s = HomeStrings.of(context);
    const onGradient = Colors.white;

    return DecoratedBox(
      decoration: BoxDecoration(
        borderRadius: AppRadius.xlAll,
        boxShadow: AppGradients.glow(context.colors.primary, opacity: 0.45),
      ),
      child: ClipRRect(
        borderRadius: AppRadius.xlAll,
        child: Stack(
          children: [
            // Base gradient.
            const Positioned.fill(
              child: DecoratedBox(decoration: BoxDecoration(gradient: AppGradients.hero)),
            ),
            // Decorative bloom circles for depth.
            Positioned(
              top: -50,
              right: -30,
              child: _Bloom(size: 160, color: Colors.white.withValues(alpha: 0.14)),
            ),
            Positioned(
              bottom: -60,
              left: -40,
              child: _Bloom(size: 150, color: Colors.black.withValues(alpha: 0.10)),
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
                          color: Colors.white.withValues(alpha: 0.18),
                          borderRadius: AppRadius.smAll,
                        ),
                        child: const Icon(Icons.savings_rounded, color: onGradient, size: 18),
                      ),
                      const SizedBox(width: AppSpacing.sm),
                      Text(
                        s.balanceTitle,
                        style: context.textTheme.labelLarge?.copyWith(
                          color: onGradient.withValues(alpha: 0.9),
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
                          _formatCoins(balance.coinBalance),
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
                        style: context.textTheme.titleMedium?.copyWith(
                          color: onGradient.withValues(alpha: 0.9),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: AppSpacing.xs),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.16),
                      borderRadius: AppRadius.pillAll,
                    ),
                    child: Text(
                      '≈ ${balance.currency} ${balance.cashBalance}',
                      style: context.textTheme.bodyMedium?.copyWith(
                        color: onGradient,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                  const SizedBox(height: AppSpacing.xl),
                  Row(
                    children: [
                      _Metric(label: s.availableLabel, value: _formatCoins(balance.available)),
                      Container(
                        width: 1,
                        height: 34,
                        margin: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
                        color: Colors.white.withValues(alpha: 0.22),
                      ),
                      _Metric(label: s.reservedLabel, value: _formatCoins(balance.coinReserved)),
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
          style: context.textTheme.bodySmall?.copyWith(
            color: onGradient.withValues(alpha: 0.82),
          ),
        ),
        const SizedBox(height: 2),
        Text(
          value,
          style: context.textTheme.titleMedium?.copyWith(
            color: onGradient,
            fontWeight: FontWeight.w700,
          ),
        ),
      ],
    );
  }
}

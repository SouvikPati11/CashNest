import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../../core/theme/app_radius.dart';
import '../../../../core/theme/app_spacing.dart';
import '../../../../shared/extensions/context_extensions.dart';
import '../../../offerwall/resources/offer_formatters.dart';
import '../../l10n/referral_strings.dart';
import '../../models/referral_info.dart';

/// The referral header: code, sharing actions, stats, and an apply-code entry.
class ReferralInfoCard extends StatelessWidget {
  const ReferralInfoCard({required this.info, required this.onApplyCode, super.key});

  final ReferralInfo info;
  final VoidCallback onApplyCode;

  Future<void> _copy(BuildContext context, String value) async {
    final s = ReferralStrings.of(context);
    await Clipboard.setData(ClipboardData(text: value));
    if (context.mounted) {
      ScaffoldMessenger.of(context)
        ..hideCurrentSnackBar()
        ..showSnackBar(SnackBar(content: Text(s.copied)));
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = ReferralStrings.of(context);
    final localeCode = Localizations.localeOf(context).languageCode;

    return Padding(
      padding: const EdgeInsets.all(AppSpacing.screen),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(AppSpacing.lg),
            decoration: BoxDecoration(
              borderRadius: AppRadius.lgAll,
              gradient: LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [context.colors.primary, context.colors.tertiary],
              ),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  s.yourCode,
                  style: context.textTheme.bodyMedium?.copyWith(color: context.colors.onPrimary),
                ),
                const SizedBox(height: AppSpacing.xs),
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        info.referralCode.isEmpty ? '—' : info.referralCode,
                        style: context.textTheme.headlineSmall?.copyWith(
                          color: context.colors.onPrimary,
                          fontWeight: FontWeight.w800,
                          letterSpacing: 2,
                        ),
                      ),
                    ),
                    if (info.referralCode.isNotEmpty)
                      IconButton(
                        onPressed: () => _copy(context, info.referralCode),
                        icon: Icon(Icons.copy, color: context.colors.onPrimary),
                        tooltip: s.copyCode,
                      ),
                  ],
                ),
                const SizedBox(height: AppSpacing.sm),
                if (info.referralLink.isNotEmpty)
                  SizedBox(
                    width: double.infinity,
                    child: FilledButton.tonalIcon(
                      onPressed: () => _copy(context, info.referralLink),
                      icon: const Icon(Icons.share),
                      label: Text(s.copyLink),
                    ),
                  ),
              ],
            ),
          ),
          const SizedBox(height: AppSpacing.lg),
          Row(
            children: [
              _Stat(label: s.totalReferrals, value: '${info.totalReferrals}'),
              _Stat(label: s.qualified, value: '${info.qualified}'),
              _Stat(
                label: s.totalEarned,
                value: OfferFormatters.coins(info.totalEarnedCoins, localeCode: localeCode),
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.md),
          Align(
            alignment: Alignment.centerLeft,
            child: TextButton.icon(
              onPressed: onApplyCode,
              icon: const Icon(Icons.redeem),
              label: Text(s.haveACode),
            ),
          ),
        ],
      ),
    );
  }
}

class _Stat extends StatelessWidget {
  const _Stat({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Column(
        children: [
          Text(
            value,
            style: context.textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800),
          ),
          Text(
            label,
            style: context.textTheme.bodySmall?.copyWith(color: context.colors.onSurfaceVariant),
          ),
        ],
      ),
    );
  }
}

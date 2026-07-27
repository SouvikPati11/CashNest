import 'package:flutter/material.dart';

import '../../../../core/theme/app_radius.dart';
import '../../../../core/theme/app_spacing.dart';
import '../../../../shared/extensions/context_extensions.dart';
import '../../l10n/wallet_strings.dart';
import '../../models/wallet_transaction.dart';
import '../../resources/wallet_formatters.dart';

/// A single transaction row in the history list.
class TransactionTile extends StatelessWidget {
  const TransactionTile({required this.transaction, this.onTap, super.key});

  final WalletTransaction transaction;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final s = WalletStrings.of(context);
    final localeCode = Localizations.localeOf(context).languageCode;
    final isCredit = transaction.isCredit;
    final accent = isCredit ? Colors.green.shade600 : context.colors.error;

    final title = (transaction.description != null && transaction.description!.isNotEmpty)
        ? transaction.description!
        : s.typeLabel(transaction.type);

    final subtitle = transaction.createdAt != null
        ? WalletFormatters.dateTime(transaction.createdAt!)
        : s.typeLabel(transaction.type);

    return InkWell(
      onTap: onTap,
      borderRadius: AppRadius.mdAll,
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: AppSpacing.sm, horizontal: AppSpacing.xs),
        child: Row(
          children: [
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                color: accent.withValues(alpha: 0.12),
                borderRadius: AppRadius.smAll,
              ),
              child: Icon(
                isCredit ? Icons.arrow_downward : Icons.arrow_upward,
                size: 20,
                color: accent,
              ),
            ),
            const SizedBox(width: AppSpacing.md),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: context.textTheme.bodyLarge,
                  ),
                  const SizedBox(height: 2),
                  Text(
                    subtitle,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: context.textTheme.bodySmall
                        ?.copyWith(color: context.colors.onSurfaceVariant),
                  ),
                ],
              ),
            ),
            const SizedBox(width: AppSpacing.sm),
            Text(
              WalletFormatters.signedCoins(transaction.amount, isCredit: isCredit, localeCode: localeCode),
              style: context.textTheme.titleMedium?.copyWith(
                color: accent,
                fontWeight: FontWeight.w700,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

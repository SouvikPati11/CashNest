import 'package:flutter/material.dart';

import '../../../../core/theme/app_radius.dart';
import '../../../../core/theme/app_spacing.dart';
import '../../../../shared/extensions/context_extensions.dart';
import '../../l10n/home_strings.dart';
import '../../models/home_transaction.dart';

/// A compact preview of the most recent wallet transactions.
///
/// Shows an inline empty state when there is no activity. The full ledger lives
/// in the wallet module; [onViewAll] lets the host route there.
class RecentTransactionsPreview extends StatelessWidget {
  const RecentTransactionsPreview({
    required this.transactions,
    this.onViewAll,
    super.key,
  });

  final List<HomeTransaction> transactions;
  final VoidCallback? onViewAll;

  @override
  Widget build(BuildContext context) {
    final s = HomeStrings.of(context);

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.lg),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(s.recentTransactions, style: context.textTheme.titleMedium),
                ),
                if (onViewAll != null && transactions.isNotEmpty)
                  TextButton(onPressed: onViewAll, child: Text(s.viewAll)),
              ],
            ),
            const SizedBox(height: AppSpacing.xs),
            if (transactions.isEmpty)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: AppSpacing.lg),
                child: Text(
                  s.noTransactions,
                  style: context.textTheme.bodyMedium
                      ?.copyWith(color: context.colors.onSurfaceVariant),
                ),
              )
            else
              for (final tx in transactions) _TransactionRow(transaction: tx),
          ],
        ),
      ),
    );
  }
}

class _TransactionRow extends StatelessWidget {
  const _TransactionRow({required this.transaction});

  final HomeTransaction transaction;

  @override
  Widget build(BuildContext context) {
    final isCredit = transaction.isCredit;
    final accent = isCredit ? Colors.green.shade600 : context.colors.error;
    final title = (transaction.description != null && transaction.description!.isNotEmpty)
        ? transaction.description!
        : _humanizeType(transaction.type);

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: AppSpacing.sm),
      child: Row(
        children: [
          Container(
            width: 36,
            height: 36,
            decoration: BoxDecoration(
              color: accent.withValues(alpha: 0.12),
              borderRadius: AppRadius.smAll,
            ),
            child: Icon(
              isCredit ? Icons.arrow_downward : Icons.arrow_upward,
              size: 18,
              color: accent,
            ),
          ),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Text(
              title,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: context.textTheme.bodyMedium,
            ),
          ),
          const SizedBox(width: AppSpacing.sm),
          Text(
            '${isCredit ? '+' : '-'}${transaction.amount}',
            style: context.textTheme.titleSmall?.copyWith(
              color: accent,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }

  String _humanizeType(String type) {
    if (type.isEmpty) {
      return '—';
    }
    return type
        .split(RegExp(r'[_\s]+'))
        .where((w) => w.isNotEmpty)
        .map((w) => w[0].toUpperCase() + w.substring(1))
        .join(' ');
  }
}

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/error/app_exception.dart';
import '../../../core/theme/app_radius.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../shared/extensions/context_extensions.dart';
import '../../../shared/widgets/app_scaffold.dart';
import '../../../shared/widgets/error_view.dart';
import '../../../shared/widgets/loading_widget.dart';
import '../l10n/wallet_strings.dart';
import '../models/wallet_transaction.dart';
import '../providers/wallet_providers.dart';
import '../resources/wallet_formatters.dart';

/// Detail view for a single transaction (metadata + related transaction).
class TransactionDetailScreen extends ConsumerWidget {
  const TransactionDetailScreen({required this.uuid, super.key});

  final String uuid;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = WalletStrings.of(context);
    final detail = ref.watch(transactionDetailProvider(uuid));

    return AppScaffold(
      appBar: AppBar(title: Text(s.transactionDetails)),
      body: detail.when(
        loading: () => const LoadingWidget(),
        error: (error, _) => ErrorView(
          message: error is AppException ? error.message : s.errorTitle,
          onRetry: () => ref.invalidate(transactionDetailProvider(uuid)),
        ),
        data: (transaction) => _DetailBody(transaction: transaction),
      ),
    );
  }
}

class _DetailBody extends StatelessWidget {
  const _DetailBody({required this.transaction});

  final WalletTransaction transaction;

  @override
  Widget build(BuildContext context) {
    final s = WalletStrings.of(context);
    final localeCode = Localizations.localeOf(context).languageCode;
    final isCredit = transaction.isCredit;
    final accent = isCredit ? Colors.green.shade600 : context.colors.error;

    return ListView(
      padding: const EdgeInsets.all(AppSpacing.screen),
      children: [
        Center(
          child: Column(
            children: [
              Container(
                width: 64,
                height: 64,
                decoration: BoxDecoration(
                  color: accent.withValues(alpha: 0.12),
                  borderRadius: AppRadius.lgAll,
                ),
                child: Icon(
                  isCredit ? Icons.arrow_downward : Icons.arrow_upward,
                  color: accent,
                  size: 32,
                ),
              ),
              const SizedBox(height: AppSpacing.md),
              Text(
                WalletFormatters.signedCoins(transaction.amount, isCredit: isCredit, localeCode: localeCode),
                style: context.textTheme.headlineMedium
                    ?.copyWith(color: accent, fontWeight: FontWeight.w700),
              ),
              Text(
                s.typeLabel(transaction.type),
                style: context.textTheme.bodyMedium
                    ?.copyWith(color: context.colors.onSurfaceVariant),
              ),
            ],
          ),
        ),
        const SizedBox(height: AppSpacing.xl),
        Card(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg, vertical: AppSpacing.sm),
            child: Column(
              children: [
                _Row(label: s.type, value: s.typeLabel(transaction.type)),
                _Row(label: s.direction, value: isCredit ? s.credit : s.debit),
                if (transaction.balanceAfter != null)
                  _Row(
                    label: s.balanceAfter,
                    value: WalletFormatters.coins(transaction.balanceAfter!, localeCode: localeCode),
                  ),
                if (transaction.sourceModule != null && transaction.sourceModule!.isNotEmpty)
                  _Row(label: s.sourceModule, value: s.typeLabel(transaction.sourceModule!)),
                if (transaction.createdAt != null)
                  _Row(
                    label: s.date,
                    value: WalletFormatters.dateTime(transaction.createdAt!),
                  ),
                _Row(label: s.reference, value: transaction.uuid, isLast: true),
              ],
            ),
          ),
        ),
        if (transaction.description != null && transaction.description!.isNotEmpty) ...[
          const SizedBox(height: AppSpacing.lg),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(AppSpacing.lg),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(s.details, style: context.textTheme.titleSmall),
                  const SizedBox(height: AppSpacing.xs),
                  Text(transaction.description!, style: context.textTheme.bodyMedium),
                ],
              ),
            ),
          ),
        ],
        if (transaction.metadata.isNotEmpty) ...[
          const SizedBox(height: AppSpacing.lg),
          Card(
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg, vertical: AppSpacing.sm),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const SizedBox(height: AppSpacing.sm),
                  Text(s.details, style: context.textTheme.titleSmall),
                  const SizedBox(height: AppSpacing.xs),
                  for (final entry in transaction.metadata.entries)
                    _Row(label: _humanize(entry.key), value: '${entry.value}'),
                ],
              ),
            ),
          ),
        ],
        if (transaction.relatedTransaction != null) ...[
          const SizedBox(height: AppSpacing.lg),
          Text(s.relatedTransaction, style: context.textTheme.titleSmall),
          const SizedBox(height: AppSpacing.sm),
          Card(
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg, vertical: AppSpacing.sm),
              child: Column(
                children: [
                  _Row(
                    label: s.typeLabel(transaction.relatedTransaction!.type),
                    value: WalletFormatters.signedCoins(
                      transaction.relatedTransaction!.amount,
                      isCredit: transaction.relatedTransaction!.isCredit,
                      localeCode: localeCode,
                    ),
                    isLast: true,
                  ),
                ],
              ),
            ),
          ),
        ],
      ],
    );
  }

  String _humanize(String key) {
    return key
        .split(RegExp(r'[_\s]+'))
        .where((w) => w.isNotEmpty)
        .map((w) => w[0].toUpperCase() + w.substring(1))
        .join(' ');
  }
}

class _Row extends StatelessWidget {
  const _Row({required this.label, required this.value, this.isLast = false});

  final String label;
  final String value;
  final bool isLast;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(vertical: AppSpacing.md),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                flex: 2,
                child: Text(
                  label,
                  style: context.textTheme.bodyMedium
                      ?.copyWith(color: context.colors.onSurfaceVariant),
                ),
              ),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                flex: 3,
                child: Text(
                  value,
                  textAlign: TextAlign.end,
                  style: context.textTheme.bodyMedium,
                ),
              ),
            ],
          ),
        ),
        if (!isLast) Divider(height: 1, color: context.colors.outlineVariant),
      ],
    );
  }
}

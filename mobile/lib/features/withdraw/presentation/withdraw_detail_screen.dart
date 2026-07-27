import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/error/app_exception.dart';
import '../../../core/network/api_result.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../shared/extensions/context_extensions.dart';
import '../../../shared/widgets/app_scaffold.dart';
import '../../../shared/widgets/error_view.dart';
import '../../../shared/widgets/loading_widget.dart';
import '../l10n/withdraw_strings.dart';
import '../models/withdraw_detail.dart';
import '../providers/withdraw_providers.dart';
import '../resources/withdraw_formatters.dart';
import 'widgets/status_chip.dart';
import 'widgets/status_timeline.dart';

/// Withdrawal detail with status timeline and (for pending requests) cancel.
class WithdrawDetailScreen extends ConsumerStatefulWidget {
  const WithdrawDetailScreen({required this.uuid, super.key});

  final String uuid;

  @override
  ConsumerState<WithdrawDetailScreen> createState() => _WithdrawDetailScreenState();
}

class _WithdrawDetailScreenState extends ConsumerState<WithdrawDetailScreen> {
  bool _cancelling = false;

  Future<void> _cancel() async {
    final s = WithdrawStrings.of(context);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: Text(s.cancelConfirmTitle),
        content: Text(s.cancelConfirmBody),
        actions: [
          TextButton(onPressed: () => Navigator.of(dialogContext).pop(false), child: Text(s.keep)),
          FilledButton(onPressed: () => Navigator.of(dialogContext).pop(true), child: Text(s.cancelRequest)),
        ],
      ),
    );
    if (confirmed != true) {
      return;
    }
    setState(() => _cancelling = true);
    try {
      final result = await ref.read(withdrawRepositoryProvider).cancel(widget.uuid);
      if (!mounted) {
        return;
      }
      switch (result) {
        case ApiSuccess():
          ref.invalidate(withdrawDetailProvider(widget.uuid));
          ref.read(withdrawHistoryControllerProvider.notifier).refresh();
          _snack(s.cancelled);
        case ApiFailure(:final error):
          _snack(error.message);
      }
    } finally {
      if (mounted) {
        setState(() => _cancelling = false);
      }
    }
  }

  void _snack(String message) {
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text(message)));
  }

  @override
  Widget build(BuildContext context) {
    final s = WithdrawStrings.of(context);
    final detail = ref.watch(withdrawDetailProvider(widget.uuid));

    return AppScaffold(
      appBar: AppBar(title: Text(s.detailTitle)),
      body: detail.when(
        loading: () => const LoadingWidget(),
        error: (error, _) => ErrorView(
          message: error is AppException ? error.message : s.errorTitle,
          onRetry: () => ref.invalidate(withdrawDetailProvider(widget.uuid)),
        ),
        data: (data) => _Body(detail: data, cancelling: _cancelling, onCancel: _cancel),
      ),
    );
  }
}

class _Body extends StatelessWidget {
  const _Body({required this.detail, required this.cancelling, required this.onCancel});

  final WithdrawDetail detail;
  final bool cancelling;
  final VoidCallback onCancel;

  @override
  Widget build(BuildContext context) {
    final s = WithdrawStrings.of(context);
    final localeCode = Localizations.localeOf(context).languageCode;
    final r = detail.request;

    return ListView(
      padding: const EdgeInsets.all(AppSpacing.screen),
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(
              '${WithdrawFormatters.coins(r.coinsAmount, localeCode: localeCode)} ${s.coins}',
              style: context.textTheme.headlineSmall,
            ),
            WithdrawStatusChip(status: r.status),
          ],
        ),
        const SizedBox(height: AppSpacing.lg),
        Card(
          child: Padding(
            padding: const EdgeInsets.all(AppSpacing.lg),
            child: Column(
              children: [
                _Kv(label: s.cashValue, value: WithdrawFormatters.cash(r.cashAmount, r.currency)),
                const SizedBox(height: AppSpacing.sm),
                _Kv(label: s.fee, value: WithdrawFormatters.cash(r.feeAmount, r.currency)),
                const Divider(height: AppSpacing.xl),
                _Kv(
                  label: s.youReceive,
                  value: WithdrawFormatters.cash(r.netAmount, r.currency),
                  emphasize: true,
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: AppSpacing.xl),
        Text(s.statusTimeline, style: context.textTheme.titleMedium),
        const SizedBox(height: AppSpacing.md),
        StatusTimeline(events: detail.history),
        if (r.isCancellable) ...[
          const SizedBox(height: AppSpacing.lg),
          SizedBox(
            width: double.infinity,
            child: OutlinedButton.icon(
              onPressed: cancelling ? null : onCancel,
              icon: cancelling
                  ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2))
                  : const Icon(Icons.close),
              label: Text(s.cancelRequest),
            ),
          ),
        ],
      ],
    );
  }
}

class _Kv extends StatelessWidget {
  const _Kv({required this.label, required this.value, this.emphasize = false});

  final String label;
  final String value;
  final bool emphasize;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: emphasize
              ? context.textTheme.titleMedium
              : context.textTheme.bodyMedium?.copyWith(color: context.colors.onSurfaceVariant),
        ),
        Text(
          value,
          style: emphasize
              ? context.textTheme.titleMedium?.copyWith(color: context.colors.primary, fontWeight: FontWeight.w800)
              : context.textTheme.bodyMedium,
        ),
      ],
    );
  }
}

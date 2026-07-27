import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/error/app_exception.dart';
import '../../../../core/theme/app_spacing.dart';
import '../../../../shared/extensions/context_extensions.dart';
import '../../l10n/withdraw_strings.dart';
import '../../models/withdraw_request.dart';
import '../../providers/withdraw_providers.dart';
import '../../resources/withdraw_formatters.dart';

/// Shows the withdrawal summary (review) sheet and submits on confirm.
/// Resolves to the created [WithdrawRequest] on success, or `null` if dismissed.
Future<WithdrawRequest?> showWithdrawSummarySheet(BuildContext context) {
  return showModalBottomSheet<WithdrawRequest>(
    context: context,
    isScrollControlled: true,
    showDragHandle: true,
    builder: (context) => const _SummarySheet(),
  );
}

class _SummarySheet extends ConsumerStatefulWidget {
  const _SummarySheet();

  @override
  ConsumerState<_SummarySheet> createState() => _SummarySheetState();
}

class _SummarySheetState extends ConsumerState<_SummarySheet> {
  String? _error;

  Future<void> _confirm() async {
    setState(() => _error = null);
    try {
      final request = await ref.read(withdrawFormControllerProvider.notifier).submit();
      if (mounted) {
        Navigator.of(context).pop(request);
      }
    } on AppException catch (e) {
      if (mounted) {
        setState(() => _error = e.message);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = WithdrawStrings.of(context);
    final state = ref.watch(withdrawFormControllerProvider);
    final localeCode = Localizations.localeOf(context).languageCode;
    final quote = state.quote;
    final method = state.method;

    return SafeArea(
      child: Padding(
        padding: EdgeInsets.only(
          left: AppSpacing.lg,
          right: AppSpacing.lg,
          bottom: MediaQuery.viewInsetsOf(context).bottom + AppSpacing.lg,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(s.summary, style: context.textTheme.titleLarge),
            const SizedBox(height: AppSpacing.lg),
            if (method != null) _Row(label: s.method, value: method.name),
            _Row(
              label: s.amount,
              value: '${WithdrawFormatters.coins(quote.coins, localeCode: localeCode)} ${s.coins}',
            ),
            _Row(label: s.cashValue, value: WithdrawFormatters.cashValue(quote.cash, quote.currency)),
            _Row(label: s.fee, value: '- ${WithdrawFormatters.cashValue(quote.fee, quote.currency)}'),
            for (final field in method?.detailFields ?? const <String>[])
              _Row(label: s.fieldLabel(field), value: state.paymentDetail[field] ?? '—'),
            const Divider(height: AppSpacing.xl),
            _Row(
              label: s.youReceive,
              value: WithdrawFormatters.cashValue(quote.net, quote.currency),
              emphasize: true,
            ),
            if (_error != null) ...[
              const SizedBox(height: AppSpacing.md),
              Text(_error!, style: context.textTheme.bodyMedium?.copyWith(color: context.colors.error)),
            ],
            const SizedBox(height: AppSpacing.xl),
            SizedBox(
              width: double.infinity,
              child: FilledButton(
                onPressed: state.submitting ? null : _confirm,
                child: state.submitting
                    ? Text(s.submitting)
                    : Text(s.confirm),
              ),
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
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: AppSpacing.xs),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Flexible(
            child: Text(
              label,
              style: emphasize
                  ? context.textTheme.titleMedium
                  : context.textTheme.bodyMedium?.copyWith(color: context.colors.onSurfaceVariant),
            ),
          ),
          const SizedBox(width: AppSpacing.md),
          Flexible(
            child: Text(
              value,
              textAlign: TextAlign.end,
              style: emphasize
                  ? context.textTheme.titleMedium?.copyWith(color: context.colors.primary, fontWeight: FontWeight.w800)
                  : context.textTheme.bodyMedium,
            ),
          ),
        ],
      ),
    );
  }
}

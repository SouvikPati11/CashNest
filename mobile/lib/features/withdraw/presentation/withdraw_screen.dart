import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/error/app_exception.dart';
import '../../../core/responsive/responsive.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../shared/extensions/context_extensions.dart';
import '../../../shared/widgets/app_scaffold.dart';
import '../../../shared/widgets/error_view.dart';
import '../../offerwall/presentation/widgets/list_skeleton.dart';
import '../application/withdraw_form_state.dart';
import '../l10n/withdraw_strings.dart';
import '../models/withdraw_method.dart';
import '../providers/withdraw_providers.dart';
import '../resources/withdraw_formatters.dart';
import 'widgets/quote_summary_card.dart';
import 'widgets/withdraw_summary_sheet.dart';
import 'withdraw_detail_screen.dart';
import 'withdraw_history_screen.dart';

/// The withdrawal form: method selection, amount, dynamic payment details, and
/// a live coin→cash/fee/net summary, leading into the review + submit flow.
class WithdrawScreen extends ConsumerStatefulWidget {
  const WithdrawScreen({super.key});

  @override
  ConsumerState<WithdrawScreen> createState() => _WithdrawScreenState();
}

class _WithdrawScreenState extends ConsumerState<WithdrawScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(withdrawFormControllerProvider.notifier).load();
    });
  }

  Future<void> _review() async {
    final request = await showWithdrawSummarySheet(context);
    if (request == null || !mounted) {
      return;
    }
    final s = WithdrawStrings.of(context);
    ref.read(withdrawHistoryControllerProvider.notifier).refresh();
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text(s.requestSubmitted)));
    await Navigator.of(context).push(
      MaterialPageRoute<void>(builder: (_) => WithdrawDetailScreen(uuid: request.uuid)),
    );
  }

  void _openHistory() {
    Navigator.of(context).push(
      MaterialPageRoute<void>(builder: (_) => const WithdrawHistoryScreen()),
    );
  }

  @override
  Widget build(BuildContext context) {
    final s = WithdrawStrings.of(context);
    final state = ref.watch(withdrawFormControllerProvider);

    return AppScaffold(
      appBar: AppBar(
        title: Text(s.title),
        actions: [
          IconButton(
            tooltip: s.historyTitle,
            onPressed: _openHistory,
            icon: const Icon(Icons.history),
          ),
        ],
      ),
      body: switch (state.loadStatus) {
        WithdrawLoad.loading => const ListSkeleton(itemCount: 4, itemHeight: 72),
        WithdrawLoad.error => _ErrorState(
            error: state.loadError,
            onRetry: () => ref.read(withdrawFormControllerProvider.notifier).load(),
          ),
        WithdrawLoad.ready => _Form(state: state, onReview: _review),
      },
    );
  }
}

class _Form extends ConsumerWidget {
  const _Form({required this.state, required this.onReview});

  final WithdrawFormState state;
  final VoidCallback onReview;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = WithdrawStrings.of(context);
    final localeCode = Localizations.localeOf(context).languageCode;
    final controller = ref.read(withdrawFormControllerProvider.notifier);
    final method = state.method;

    String? amountError;
    if (state.coins > 0 && method != null) {
      if (state.coins < method.minCoins) {
        amountError = s.belowMin;
      } else if (state.coins > method.maxCoins) {
        amountError = s.aboveMax;
      }
    }

    final content = ListView(
      padding: const EdgeInsets.all(AppSpacing.screen),
      children: [
        Text(s.method, style: context.textTheme.titleSmall),
        const SizedBox(height: AppSpacing.sm),
        DropdownButtonFormField<WithdrawMethod>(
          initialValue: method,
          items: [
            for (final m in state.methods)
              DropdownMenuItem(value: m, child: Text(m.name)),
          ],
          onChanged: (m) {
            if (m != null) {
              controller.selectMethod(m);
            }
          },
        ),
        if (method != null) ...[
          const SizedBox(height: AppSpacing.xs),
          Text(
            s.minMax(
              WithdrawFormatters.coins(method.minCoins, localeCode: localeCode),
              WithdrawFormatters.coins(method.maxCoins, localeCode: localeCode),
            ),
            style: context.textTheme.bodySmall?.copyWith(color: context.colors.onSurfaceVariant),
          ),
        ],
        const SizedBox(height: AppSpacing.lg),
        Text(s.amount, style: context.textTheme.titleSmall),
        const SizedBox(height: AppSpacing.sm),
        TextField(
          keyboardType: TextInputType.number,
          inputFormatters: [FilteringTextInputFormatter.digitsOnly],
          decoration: InputDecoration(
            hintText: s.amountHint,
            suffixText: s.coins,
            errorText: amountError,
          ),
          onChanged: (v) => controller.setCoins(int.tryParse(v) ?? 0),
        ),
        if (method != null && method.detailFields.isNotEmpty) ...[
          const SizedBox(height: AppSpacing.lg),
          Text(s.paymentDetails, style: context.textTheme.titleSmall),
          const SizedBox(height: AppSpacing.sm),
          for (final field in method.detailFields)
            Padding(
              padding: const EdgeInsets.only(bottom: AppSpacing.md),
              child: TextField(
                key: ValueKey('${method.code}-$field'),
                decoration: InputDecoration(labelText: s.fieldLabel(field)),
                onChanged: (v) => controller.setDetail(field, v),
              ),
            ),
        ],
        const SizedBox(height: AppSpacing.lg),
        QuoteSummaryCard(quote: state.quote),
        const SizedBox(height: AppSpacing.xl),
        FilledButton(
          onPressed: state.canSubmit ? onReview : null,
          child: Text(s.review),
        ),
      ],
    );

    return ResponsiveLayout(
      phone: (context) => content,
      tablet: (context) => Center(
        child: ConstrainedBox(constraints: const BoxConstraints(maxWidth: 560), child: content),
      ),
    );
  }
}

class _ErrorState extends StatelessWidget {
  const _ErrorState({required this.error, required this.onRetry});

  final Object? error;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    final s = WithdrawStrings.of(context);
    final err = error;
    return ErrorView(
      message: err is AppException ? err.message : s.errorTitle,
      onRetry: onRetry,
    );
  }
}

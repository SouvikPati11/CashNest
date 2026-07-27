import 'package:flutter/material.dart';

import '../../../../core/theme/app_spacing.dart';
import '../../../../shared/extensions/context_extensions.dart';
import '../../l10n/wallet_strings.dart';
import '../../models/transaction_filter.dart';
import '../../resources/wallet_formatters.dart';

/// Modal bottom sheet for editing the transaction [TransactionFilter].
///
/// Returns the new filter on Apply, or `null` if dismissed. Clearing applies
/// [TransactionFilter.none].
Future<TransactionFilter?> showTransactionFilterSheet(
  BuildContext context,
  TransactionFilter current,
) {
  return showModalBottomSheet<TransactionFilter>(
    context: context,
    isScrollControlled: true,
    showDragHandle: true,
    builder: (context) => _FilterSheet(initial: current),
  );
}

class _FilterSheet extends StatefulWidget {
  const _FilterSheet({required this.initial});

  final TransactionFilter initial;

  @override
  State<_FilterSheet> createState() => _FilterSheetState();
}

class _FilterSheetState extends State<_FilterSheet> {
  late String? _type = widget.initial.type;
  late String? _direction = widget.initial.direction;
  late DateTime? _dateFrom = widget.initial.dateFrom;
  late DateTime? _dateTo = widget.initial.dateTo;

  Future<void> _pickDate({required bool isFrom}) async {
    final now = DateTime.now();
    final initial = isFrom ? (_dateFrom ?? now) : (_dateTo ?? now);
    final picked = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: DateTime(now.year - 1),
      lastDate: now,
    );
    if (picked == null) {
      return;
    }
    setState(() {
      if (isFrom) {
        _dateFrom = picked;
      } else {
        _dateTo = picked;
      }
    });
  }

  void _apply() {
    Navigator.of(context).pop(
      TransactionFilter(
        type: _type,
        direction: _direction,
        dateFrom: _dateFrom,
        dateTo: _dateTo,
      ),
    );
  }

  void _clear() => Navigator.of(context).pop(TransactionFilter.none);

  @override
  Widget build(BuildContext context) {
    final s = WalletStrings.of(context);

    return SafeArea(
      child: Padding(
        padding: EdgeInsets.only(
          left: AppSpacing.lg,
          right: AppSpacing.lg,
          bottom: MediaQuery.viewInsetsOf(context).bottom + AppSpacing.lg,
        ),
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(s.filters, style: context.textTheme.titleLarge),
              const SizedBox(height: AppSpacing.lg),

              // Direction
              Text(s.filterDirection, style: context.textTheme.titleSmall),
              const SizedBox(height: AppSpacing.sm),
              Wrap(
                spacing: AppSpacing.sm,
                children: [
                  _choice(s.all, _direction == null, () => setState(() => _direction = null)),
                  _choice(s.credit, _direction == 'credit', () => setState(() => _direction = 'credit')),
                  _choice(s.debit, _direction == 'debit', () => setState(() => _direction = 'debit')),
                ],
              ),
              const SizedBox(height: AppSpacing.lg),

              // Type
              Text(s.filterType, style: context.textTheme.titleSmall),
              const SizedBox(height: AppSpacing.sm),
              Wrap(
                spacing: AppSpacing.sm,
                runSpacing: AppSpacing.xs,
                children: [
                  _choice(s.all, _type == null, () => setState(() => _type = null)),
                  for (final type in WalletStrings.filterableTypes)
                    _choice(s.typeLabel(type), _type == type, () => setState(() => _type = type)),
                ],
              ),
              const SizedBox(height: AppSpacing.lg),

              // Date range
              Text(s.dateRange, style: context.textTheme.titleSmall),
              const SizedBox(height: AppSpacing.sm),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      icon: const Icon(Icons.calendar_today, size: 18),
                      onPressed: () => _pickDate(isFrom: true),
                      label: Text(
                        _dateFrom != null
                            ? WalletFormatters.date(_dateFrom!)
                            : s.from,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ),
                  const SizedBox(width: AppSpacing.md),
                  Expanded(
                    child: OutlinedButton.icon(
                      icon: const Icon(Icons.calendar_today, size: 18),
                      onPressed: () => _pickDate(isFrom: false),
                      label: Text(
                        _dateTo != null
                            ? WalletFormatters.date(_dateTo!)
                            : s.to,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: AppSpacing.xl),

              Row(
                children: [
                  Expanded(
                    child: OutlinedButton(onPressed: _clear, child: Text(s.clear)),
                  ),
                  const SizedBox(width: AppSpacing.md),
                  Expanded(
                    child: FilledButton(onPressed: _apply, child: Text(s.apply)),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _choice(String label, bool selected, VoidCallback onTap) {
    return ChoiceChip(
      label: Text(label),
      selected: selected,
      onSelected: (_) => onTap(),
    );
  }
}

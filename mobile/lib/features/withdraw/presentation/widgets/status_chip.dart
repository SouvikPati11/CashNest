import 'package:flutter/material.dart';

import '../../../../shared/extensions/context_extensions.dart';
import '../../l10n/withdraw_strings.dart';
import '../../resources/withdraw_formatters.dart';

/// A colored status chip for a withdrawal status.
class WithdrawStatusChip extends StatelessWidget {
  const WithdrawStatusChip({required this.status, super.key});

  final String status;

  @override
  Widget build(BuildContext context) {
    final s = WithdrawStrings.of(context);
    final color = WithdrawFormatters.statusColor(status, context.colors);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.14),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        s.statusLabel(status),
        style: context.textTheme.labelMedium?.copyWith(color: color, fontWeight: FontWeight.w700),
      ),
    );
  }
}

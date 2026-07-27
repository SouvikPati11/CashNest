import 'package:flutter/material.dart';

import '../../../../core/theme/app_spacing.dart';
import '../../../../shared/extensions/context_extensions.dart';
import '../../l10n/withdraw_strings.dart';
import '../../models/withdraw_detail.dart';
import '../../resources/withdraw_formatters.dart';

/// A vertical status timeline for a withdrawal request.
class StatusTimeline extends StatelessWidget {
  const StatusTimeline({required this.events, super.key});

  final List<WithdrawEvent> events;

  @override
  Widget build(BuildContext context) {
    if (events.isEmpty) {
      return const SizedBox.shrink();
    }
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        for (var i = 0; i < events.length; i++)
          _TimelineRow(
            event: events[i],
            isFirst: i == 0,
            isLast: i == events.length - 1,
          ),
      ],
    );
  }
}

class _TimelineRow extends StatelessWidget {
  const _TimelineRow({required this.event, required this.isFirst, required this.isLast});

  final WithdrawEvent event;
  final bool isFirst;
  final bool isLast;

  @override
  Widget build(BuildContext context) {
    final s = WithdrawStrings.of(context);
    final color = WithdrawFormatters.statusColor(event.toStatus, context.colors);

    return IntrinsicHeight(
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Column(
            children: [
              Container(
                width: 14,
                height: 14,
                margin: const EdgeInsets.only(top: 4),
                decoration: BoxDecoration(
                  color: isFirst ? color : context.colors.surface,
                  shape: BoxShape.circle,
                  border: Border.all(color: color, width: 2),
                ),
              ),
              if (!isLast)
                Expanded(
                  child: Container(width: 2, color: context.colors.outlineVariant),
                ),
            ],
          ),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Padding(
              padding: const EdgeInsets.only(bottom: AppSpacing.lg),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    s.statusLabel(event.toStatus),
                    style: context.textTheme.titleSmall?.copyWith(color: color, fontWeight: FontWeight.w700),
                  ),
                  if (event.note != null && event.note!.isNotEmpty)
                    Text(event.note!, style: context.textTheme.bodySmall),
                  if (event.createdAt != null)
                    Text(
                      WithdrawFormatters.dateTime(event.createdAt!),
                      style: context.textTheme.labelSmall
                          ?.copyWith(color: context.colors.onSurfaceVariant),
                    ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

import 'package:flutter/material.dart';

import '../../../../core/theme/app_radius.dart';
import '../../../../shared/extensions/context_extensions.dart';
import '../../l10n/withdraw_strings.dart';
import '../../models/withdraw_request.dart';
import '../../resources/withdraw_formatters.dart';
import 'status_chip.dart';

/// A withdrawal row in the history list.
class WithdrawRequestTile extends StatelessWidget {
  const WithdrawRequestTile({required this.request, this.onTap, super.key});

  final WithdrawRequest request;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final s = WithdrawStrings.of(context);
    final localeCode = Localizations.localeOf(context).languageCode;

    return Card(
      child: ListTile(
        shape: const RoundedRectangleBorder(borderRadius: AppRadius.mdAll),
        onTap: onTap,
        leading: CircleAvatar(
          backgroundColor: context.colors.secondaryContainer,
          child: Icon(Icons.payments_outlined, color: context.colors.onSecondaryContainer, size: 20),
        ),
        title: Text(
          '${WithdrawFormatters.coins(request.coinsAmount, localeCode: localeCode)} ${s.coins}',
          style: context.textTheme.titleSmall,
        ),
        subtitle: Text(
          request.createdAt != null
              ? WithdrawFormatters.dateTime(request.createdAt!)
              : WithdrawFormatters.cash(request.netAmount, request.currency),
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
        trailing: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            WithdrawStatusChip(status: request.status),
            const SizedBox(height: 4),
            Text(
              WithdrawFormatters.cash(request.netAmount, request.currency),
              style: context.textTheme.labelMedium
                  ?.copyWith(color: context.colors.onSurfaceVariant),
            ),
          ],
        ),
      ),
    );
  }
}

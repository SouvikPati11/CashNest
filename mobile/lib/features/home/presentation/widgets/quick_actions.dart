import 'package:flutter/material.dart';

import '../../../../core/theme/app_radius.dart';
import '../../../../core/theme/app_spacing.dart';
import '../../../../shared/extensions/context_extensions.dart';
import '../../../../shared/widgets/ds/icon_badge.dart';
import '../../l10n/home_strings.dart';

/// A horizontal grid of quick-action shortcuts driven by the layout config.
///
/// Each entry is a known action key (e.g. `earn`, `spin`, `refer`); labels are
/// localized and icons resolved by [_iconFor]. Unknown keys fall back to a
/// generic icon so newer server keys still render.
class QuickActions extends StatelessWidget {
  const QuickActions({
    required this.actionKeys,
    this.onAction,
    super.key,
  });

  final List<String> actionKeys;
  final void Function(String actionKey)? onAction;

  @override
  Widget build(BuildContext context) {
    if (actionKeys.isEmpty) {
      return const SizedBox.shrink();
    }
    final s = HomeStrings.of(context);

    return Wrap(
      spacing: AppSpacing.md,
      runSpacing: AppSpacing.md,
      children: [
        for (final key in actionKeys)
          _QuickAction(
            icon: _iconFor(key),
            label: s.actionLabel(key),
            onTap: onAction == null ? null : () => onAction!(key),
          ),
      ],
    );
  }

  static IconData _iconFor(String key) {
    switch (key) {
      case 'earn':
        return Icons.paid_outlined;
      case 'spin':
        return Icons.casino_outlined;
      case 'scratch':
        return Icons.card_giftcard_outlined;
      case 'checkin':
        return Icons.event_available_outlined;
      case 'offers':
        return Icons.ballot_outlined;
      case 'tasks':
        return Icons.checklist_outlined;
      case 'refer':
        return Icons.group_add_outlined;
      case 'wallet':
        return Icons.account_balance_wallet_outlined;
      case 'withdraw':
        return Icons.savings_outlined;
      case 'leaderboard':
        return Icons.leaderboard_outlined;
      default:
        return Icons.apps_outlined;
    }
  }
}

class _QuickAction extends StatelessWidget {
  const _QuickAction({required this.icon, required this.label, this.onTap});

  final IconData icon;
  final String label;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 76,
      child: InkWell(
        onTap: onTap,
        borderRadius: AppRadius.lgAll,
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: AppSpacing.sm),
          child: Column(
            children: [
              IconBadge(icon: icon, size: 56, iconSize: 26, radius: AppRadius.lgAll),
              const SizedBox(height: AppSpacing.sm),
              Text(
                label,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                textAlign: TextAlign.center,
                style: context.textTheme.labelMedium?.copyWith(fontWeight: FontWeight.w600),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

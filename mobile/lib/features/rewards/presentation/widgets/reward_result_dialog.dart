import 'package:flutter/material.dart';

import '../../../../core/theme/app_radius.dart';
import '../../../../core/theme/app_spacing.dart';
import '../../../../shared/extensions/context_extensions.dart';
import '../../l10n/rewards_strings.dart';
import '../../resources/reward_visuals.dart';

/// Shows an animated reward-earned dialog (the shared "claim animation").
///
/// Pass [coins] earned; [title]/[message] override the defaults. When [coins]
/// is 0 (e.g. a non-coin spin outcome) a "better luck" style message is shown.
Future<void> showRewardResult(
  BuildContext context, {
  required int coins,
  String? title,
  String? message,
}) {
  return showDialog<void>(
    context: context,
    barrierDismissible: true,
    builder: (context) => _RewardResultDialog(coins: coins, title: title, message: message),
  );
}

class _RewardResultDialog extends StatefulWidget {
  const _RewardResultDialog({required this.coins, this.title, this.message});

  final int coins;
  final String? title;
  final String? message;

  @override
  State<_RewardResultDialog> createState() => _RewardResultDialogState();
}

class _RewardResultDialogState extends State<_RewardResultDialog>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 900),
  )..forward();

  late final Animation<double> _pop = CurvedAnimation(
    parent: _controller,
    curve: const Interval(0, 0.6, curve: Curves.elasticOut),
  );

  late final Animation<double> _spin = CurvedAnimation(
    parent: _controller,
    curve: const Interval(0, 0.7, curve: Curves.easeOut),
  );

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final s = RewardsStrings.of(context);
    final localeCode = Localizations.localeOf(context).languageCode;
    final hasCoins = widget.coins > 0;

    return Dialog(
      shape: const RoundedRectangleBorder(borderRadius: AppRadius.xlAll),
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.xl),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ScaleTransition(
              scale: _pop,
              child: RotationTransition(
                turns: Tween<double>(begin: -0.15, end: 0).animate(_spin),
                child: Container(
                  width: 96,
                  height: 96,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    gradient: LinearGradient(
                      colors: [context.colors.primary, context.colors.tertiary],
                    ),
                  ),
                  child: Icon(
                    hasCoins ? Icons.monetization_on : Icons.sentiment_satisfied_alt,
                    size: 52,
                    color: context.colors.onPrimary,
                  ),
                ),
              ),
            ),
            const SizedBox(height: AppSpacing.lg),
            Text(
              widget.title ?? (hasCoins ? s.congrats : s.betterLuck),
              style: context.textTheme.titleLarge,
              textAlign: TextAlign.center,
            ),
            if (hasCoins) ...[
              const SizedBox(height: AppSpacing.sm),
              Text(s.youEarned, style: context.textTheme.bodyMedium),
              const SizedBox(height: AppSpacing.xs),
              FadeTransition(
                opacity: _spin,
                child: Text(
                  '${RewardVisuals.coins(widget.coins, localeCode: localeCode)} ${s.coins}',
                  style: context.textTheme.headlineMedium?.copyWith(
                    color: context.colors.primary,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
            ] else if (widget.message != null) ...[
              const SizedBox(height: AppSpacing.sm),
              Text(widget.message!, style: context.textTheme.bodyMedium, textAlign: TextAlign.center),
            ],
            const SizedBox(height: AppSpacing.xl),
            SizedBox(
              width: double.infinity,
              child: FilledButton(
                onPressed: () => Navigator.of(context).pop(),
                child: Text(s.awesome),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

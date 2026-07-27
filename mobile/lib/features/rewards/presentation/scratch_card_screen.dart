import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/error/app_exception.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../shared/extensions/context_extensions.dart';
import '../../../shared/widgets/app_scaffold.dart';
import '../../../shared/widgets/empty_view.dart';
import '../l10n/rewards_strings.dart';
import '../models/scratch_card.dart';
import '../providers/rewards_providers.dart';
import 'widgets/reward_error.dart';
import 'widgets/reward_result_dialog.dart';
import 'widgets/reward_skeleton.dart';
import 'widgets/scratch_card_widget.dart';

/// Scratch cards: shows available cards; tapping one reveals + scratches it.
class ScratchCardScreen extends ConsumerStatefulWidget {
  const ScratchCardScreen({super.key});

  @override
  ConsumerState<ScratchCardScreen> createState() => _ScratchCardScreenState();
}

class _ScratchCardScreenState extends ConsumerState<ScratchCardScreen> {
  String? _busyUuid;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(scratchControllerProvider.notifier).load();
    });
  }

  void _snack(String message) {
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text(message)));
  }

  Future<void> _openCard(ScratchCard card) async {
    if (_busyUuid != null) {
      return;
    }
    setState(() => _busyUuid = card.uuid);
    try {
      final revealed = card.isRevealed
          ? card
          : await ref.read(scratchControllerProvider.notifier).reveal(card.uuid);
      if (!mounted) {
        return;
      }
      await _showScratchDialog(revealed);
    } on AppException catch (e) {
      if (mounted) {
        _snack(e.message);
      }
    } finally {
      if (mounted) {
        setState(() => _busyUuid = null);
      }
    }
  }

  Future<void> _showScratchDialog(ScratchCard card) {
    final s = RewardsStrings.of(context);
    return showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) => Dialog(
        child: Padding(
          padding: const EdgeInsets.all(AppSpacing.xl),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(s.scratchSubtitle, style: context.textTheme.titleMedium),
              const SizedBox(height: AppSpacing.lg),
              ScratchCardWidget(
                rewardCoins: card.rewardCoins ?? 0,
                onCompleted: () => _claim(dialogContext, card),
              ),
              const SizedBox(height: AppSpacing.md),
              Text(
                s.scratchToReveal,
                style: context.textTheme.bodySmall
                    ?.copyWith(color: context.colors.onSurfaceVariant),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _claim(BuildContext dialogContext, ScratchCard card) async {
    try {
      final result = await ref.read(scratchControllerProvider.notifier).claim(card.uuid);
      if (dialogContext.mounted) {
        Navigator.of(dialogContext).pop();
      }
      if (mounted) {
        await showRewardResult(context, coins: result.coinsAwarded);
      }
    } on AppException catch (e) {
      if (dialogContext.mounted) {
        Navigator.of(dialogContext).pop();
      }
      if (mounted) {
        _snack(e.message);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = RewardsStrings.of(context);
    final state = ref.watch(scratchControllerProvider);

    return AppScaffold(
      appBar: AppBar(title: Text(s.scratchCards)),
      body: RefreshIndicator(
        onRefresh: () => ref.read(scratchControllerProvider.notifier).refresh(),
        child: switch (state) {
          AsyncData(:final value) => value.isEmpty
              ? _empty(s)
              : _grid(value),
          AsyncError(:final error) => RewardErrorView(
              error: error,
              onRetry: () => ref.read(scratchControllerProvider.notifier).load(),
            ),
          _ => const RewardListSkeleton(),
        },
      ),
    );
  }

  Widget _empty(RewardsStrings s) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        Padding(
          padding: const EdgeInsets.only(top: AppSpacing.xxxl),
          child: EmptyView(
            icon: Icons.card_giftcard_outlined,
            title: s.noCards,
            message: s.noCardsBody,
          ),
        ),
      ],
    );
  }

  Widget _grid(List<ScratchCard> cards) {
    return GridView.builder(
      padding: const EdgeInsets.all(AppSpacing.screen),
      physics: const AlwaysScrollableScrollPhysics(),
      gridDelegate: const SliverGridDelegateWithMaxCrossAxisExtent(
        maxCrossAxisExtent: 220,
        mainAxisSpacing: AppSpacing.md,
        crossAxisSpacing: AppSpacing.md,
        childAspectRatio: 0.9,
      ),
      itemCount: cards.length,
      itemBuilder: (context, index) {
        final card = cards[index];
        return _CardTile(
          card: card,
          busy: _busyUuid == card.uuid,
          onTap: () => _openCard(card),
        );
      },
    );
  }
}

class _CardTile extends StatelessWidget {
  const _CardTile({required this.card, required this.busy, required this.onTap});

  final ScratchCard card;
  final bool busy;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final s = RewardsStrings.of(context);
    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: busy ? null : onTap,
        child: DecoratedBox(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [context.colors.tertiaryContainer, context.colors.primaryContainer],
            ),
          ),
          child: Center(
            child: busy
                ? const CircularProgressIndicator()
                : Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.card_giftcard, size: 44, color: context.colors.onTertiaryContainer),
                      const SizedBox(height: AppSpacing.sm),
                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: AppSpacing.sm),
                        child: Text(
                          card.isRevealed ? s.tapToClaim : s.scratchToReveal,
                          textAlign: TextAlign.center,
                          style: context.textTheme.labelLarge
                              ?.copyWith(color: context.colors.onTertiaryContainer),
                        ),
                      ),
                    ],
                  ),
          ),
        ),
      ),
    );
  }
}

import 'package:flutter/material.dart';

import '../../../../core/theme/app_radius.dart';
import '../../../../core/theme/app_spacing.dart';
import '../../../../shared/extensions/context_extensions.dart';
import '../../../../shared/widgets/section_header.dart';
import '../../../rewards/presentation/daily_checkin_screen.dart';
import '../../../rewards/presentation/reward_history_screen.dart';
import '../../../rewards/presentation/scratch_card_screen.dart';
import '../../../rewards/presentation/spin_wheel_screen.dart';
import '../../../rewards/presentation/tasks_screen.dart';

/// The "Earn" hub tab: colorful gradient cards that connect to the app's
/// existing earning features (daily check-in, spin, scratch, tasks/hot offer,
/// reward history) plus a shortcut to the Surveys & Offers tab.
///
/// Purely a navigation surface — every card opens an already-built screen or
/// switches tabs; no earning logic is duplicated here.
class EarnHubTab extends StatelessWidget {
  const EarnHubTab({super.key});

  @override
  Widget build(BuildContext context) {
    void open(WidgetBuilder builder) {
      Navigator.of(context).push(MaterialPageRoute<void>(builder: builder));
    }

    final cards = <_EarnCard>[
      _EarnCard(
        icon: Icons.event_available_rounded,
        label: 'Daily Check-in',
        colors: const [Color(0xFF00B4DB), Color(0xFF0083B0)],
        onTap: () => open((_) => const DailyCheckinScreen()),
      ),
      _EarnCard(
        icon: Icons.local_fire_department_rounded,
        label: 'Hot Offer',
        colors: const [Color(0xFF3A1C71), Color(0xFF4B3FD6)],
        onTap: () => open((_) => const TasksScreen()),
      ),
      _EarnCard(
        icon: Icons.casino_rounded,
        label: 'Spin & Win',
        colors: const [Color(0xFF7B2FE0), Color(0xFF9B5CF6)],
        onTap: () => open((_) => const SpinWheelScreen()),
      ),
      _EarnCard(
        icon: Icons.card_giftcard_rounded,
        label: 'Scratch Card',
        colors: const [Color(0xFFF7971E), Color(0xFFFFA751)],
        onTap: () => open((_) => const ScratchCardScreen()),
      ),
      _EarnCard(
        icon: Icons.fact_check_rounded,
        label: 'Surveys & Offers',
        colors: const [Color(0xFF12C2E9), Color(0xFF2E86DE)],
        onTap: () => DefaultTabController.of(context).animateTo(1),
      ),
      _EarnCard(
        icon: Icons.history_rounded,
        label: 'Reward History',
        colors: const [Color(0xFF22D3A6), Color(0xFF0E9F86)],
        onTap: () => open((_) => const RewardHistoryScreen()),
      ),
    ];

    return ListView(
      padding: const EdgeInsets.all(AppSpacing.screen),
      children: [
        const SectionHeader(title: 'Earn Coins'),
        GridView.count(
          crossAxisCount: 2,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          mainAxisSpacing: AppSpacing.md,
          crossAxisSpacing: AppSpacing.md,
          childAspectRatio: 1.7,
          children: [for (final c in cards) _EarnCardView(card: c)],
        ),
      ],
    );
  }
}

class _EarnCard {
  const _EarnCard({
    required this.icon,
    required this.label,
    required this.colors,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final List<Color> colors;
  final VoidCallback onTap;
}

class _EarnCardView extends StatelessWidget {
  const _EarnCardView({required this.card});

  final _EarnCard card;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        borderRadius: AppRadius.lgAll,
        boxShadow: [
          BoxShadow(
            color: card.colors.first.withValues(alpha: 0.30),
            blurRadius: 16,
            offset: const Offset(0, 8),
            spreadRadius: -6,
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: AppRadius.lgAll,
        child: Stack(
          children: [
            Positioned.fill(
              child: DecoratedBox(
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: card.colors,
                  ),
                ),
              ),
            ),
            Positioned(
              right: -14,
              bottom: -14,
              child: Icon(card.icon, size: 78, color: Colors.white.withValues(alpha: 0.16)),
            ),
            Material(
              type: MaterialType.transparency,
              child: InkWell(
                onTap: card.onTap,
                child: Padding(
                  padding: const EdgeInsets.all(AppSpacing.md),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Container(
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.22),
                          borderRadius: AppRadius.smAll,
                        ),
                        child: Icon(card.icon, color: Colors.white, size: 20),
                      ),
                      Text(
                        card.label,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: context.textTheme.titleSmall?.copyWith(
                          color: Colors.white,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

import 'package:flutter/material.dart';

import '../../l10n/home_strings.dart';
import 'preview_card.dart';

/// Daily check-in teaser (rewards feature is a separate module).
class DailyCheckinCard extends StatelessWidget {
  const DailyCheckinCard({this.onTap, super.key});

  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final s = HomeStrings.of(context);
    return PreviewCard(
      icon: Icons.event_available_outlined,
      title: s.dailyCheckinTitle,
      subtitle: s.dailyCheckinSubtitle,
      actionLabel: s.claim,
      onTap: onTap,
    );
  }
}

/// Scratch-card teaser.
class ScratchCardPreview extends StatelessWidget {
  const ScratchCardPreview({this.onTap, super.key});

  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final s = HomeStrings.of(context);
    return PreviewCard(
      icon: Icons.card_giftcard_outlined,
      title: s.scratchTitle,
      subtitle: s.scratchSubtitle,
      onTap: onTap,
    );
  }
}

/// Spin-wheel teaser.
class SpinWheelPreview extends StatelessWidget {
  const SpinWheelPreview({this.onTap, super.key});

  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final s = HomeStrings.of(context);
    return PreviewCard(
      icon: Icons.casino_outlined,
      title: s.spinTitle,
      subtitle: s.spinSubtitle,
      onTap: onTap,
    );
  }
}

/// Offerwall teaser.
class OfferwallPreview extends StatelessWidget {
  const OfferwallPreview({this.title, this.onTap, super.key});

  final String? title;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final s = HomeStrings.of(context);
    return PreviewCard(
      icon: Icons.ballot_outlined,
      title: title ?? s.offerwallTitle,
      subtitle: s.offerwallSubtitle,
      actionLabel: s.open,
      onTap: onTap,
    );
  }
}

/// Tasks teaser.
class TasksPreview extends StatelessWidget {
  const TasksPreview({this.title, this.onTap, super.key});

  final String? title;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final s = HomeStrings.of(context);
    return PreviewCard(
      icon: Icons.checklist_outlined,
      title: title ?? s.tasksTitle,
      subtitle: s.tasksSubtitle,
      actionLabel: s.view,
      onTap: onTap,
    );
  }
}

/// Referral teaser.
class ReferralCard extends StatelessWidget {
  const ReferralCard({this.onTap, super.key});

  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final s = HomeStrings.of(context);
    return PreviewCard(
      icon: Icons.group_add_outlined,
      title: s.referralTitle,
      subtitle: s.referralSubtitle,
      actionLabel: s.invite,
      onTap: onTap,
    );
  }
}

/// Leaderboard teaser.
class LeaderboardPreview extends StatelessWidget {
  const LeaderboardPreview({this.title, this.onTap, super.key});

  final String? title;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final s = HomeStrings.of(context);
    return PreviewCard(
      icon: Icons.leaderboard_outlined,
      title: title ?? s.leaderboardTitle,
      subtitle: s.leaderboardSubtitle,
      actionLabel: s.view,
      onTap: onTap,
    );
  }
}

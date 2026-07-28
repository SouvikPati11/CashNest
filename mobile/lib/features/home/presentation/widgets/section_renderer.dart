import 'package:flutter/material.dart';

import '../../../../shared/widgets/section_header.dart';
import '../../l10n/home_strings.dart';
import '../../models/home_banner.dart';
import '../../models/home_section.dart';
import 'banner_carousel.dart';
import 'feature_previews.dart';
import 'quick_actions.dart';

/// Renders a single server-driven [HomeSection] into its widget.
///
/// Every titled section is introduced with a consistent [SectionHeader] so the
/// dashboard reads as a structured feed. Unknown or unsupported `type` values
/// render nothing, keeping the layout forward-compatible with newer section
/// types the app doesn't yet know about.
class SectionRenderer extends StatelessWidget {
  const SectionRenderer({
    required this.section,
    this.banners = const [],
    this.onAction,
    this.onBannerTap,
    super.key,
  });

  final HomeSection section;
  final List<HomeBanner> banners;
  final void Function(String actionKey)? onAction;
  final void Function(HomeBanner banner)? onBannerTap;

  @override
  Widget build(BuildContext context) {
    switch (section.type) {
      case 'banner_carousel':
        return BannerCarousel(banners: banners, onBannerTap: onBannerTap);

      case 'quick_actions':
        final keys = section.stringList('actions').isNotEmpty
            ? section.stringList('actions')
            : section.stringList('items');
        return _titled(
          context,
          section.title,
          QuickActions(actionKeys: keys, onAction: onAction),
        );

      case 'offers':
        return _titled(
          context,
          section.title,
          OfferwallPreview(onTap: onAction == null ? null : () => onAction!('offers')),
          actionLabel: HomeStrings.of(context).viewAll,
          onAction: onAction == null ? null : () => onAction!('offers'),
        );

      case 'tasks':
        return _titled(
          context,
          section.title,
          TasksPreview(onTap: onAction == null ? null : () => onAction!('tasks')),
          actionLabel: HomeStrings.of(context).viewAll,
          onAction: onAction == null ? null : () => onAction!('tasks'),
        );

      case 'leaderboard':
        return _titled(
          context,
          section.title,
          LeaderboardPreview(onTap: onAction == null ? null : () => onAction!('leaderboard')),
          actionLabel: HomeStrings.of(context).viewAll,
          onAction: onAction == null ? null : () => onAction!('leaderboard'),
        );

      case 'scratch':
        return _titled(
          context,
          section.title,
          ScratchCardPreview(onTap: onAction == null ? null : () => onAction!('scratch')),
        );

      case 'spin':
        return _titled(
          context,
          section.title,
          SpinWheelPreview(onTap: onAction == null ? null : () => onAction!('spin')),
        );

      case 'custom':
      default:
        // Unknown/newer section types are ignored gracefully.
        return const SizedBox.shrink();
    }
  }

  Widget _titled(
    BuildContext context,
    String? title,
    Widget child, {
    String? actionLabel,
    VoidCallback? onAction,
  }) {
    if (title == null || title.isEmpty) {
      return child;
    }
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SectionHeader(
          title: title,
          actionLabel: actionLabel,
          onAction: onAction,
        ),
        child,
      ],
    );
  }
}

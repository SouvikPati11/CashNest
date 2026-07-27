import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/error/app_exception.dart';
import '../../../core/responsive/responsive.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../shared/extensions/context_extensions.dart';
import '../../../shared/widgets/app_scaffold.dart';
import '../../../shared/widgets/empty_view.dart';
import '../../../shared/widgets/error_view.dart';
import '../../notification/l10n/notification_strings.dart';
import '../../notification/presentation/widgets/unread_badge.dart';
import '../l10n/home_strings.dart';
import '../models/home_announcement.dart';
import '../models/home_banner.dart';
import '../models/home_data.dart';
import '../providers/home_providers.dart';
import 'widgets/announcement_popup.dart';
import 'widgets/balance_card.dart';
import 'widgets/feature_previews.dart';
import 'widgets/home_skeleton.dart';
import 'widgets/profile_header.dart';
import 'widgets/recent_transactions_preview.dart';
import 'widgets/section_renderer.dart';

/// The home dashboard: a server-driven, pull-to-refresh dashboard composed of
/// the balance card, profile header, dynamic layout sections, and fixed feature
/// teasers. Loads on mount and surfaces skeleton/error/empty/offline states.
class HomeDashboardScreen extends ConsumerStatefulWidget {
  const HomeDashboardScreen({super.key});

  @override
  ConsumerState<HomeDashboardScreen> createState() => _HomeDashboardScreenState();
}

class _HomeDashboardScreenState extends ConsumerState<HomeDashboardScreen> {
  int? _shownAnnouncementId;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(homeControllerProvider.notifier).load();
    });
  }

  Future<void> _refresh() {
    return ref.read(homeControllerProvider.notifier).refresh();
  }

  void _maybeShowAnnouncement(HomeData data) {
    final announcement = data.topAnnouncement;
    if (announcement == null || announcement.displayType != 'popup') {
      return;
    }
    if (_shownAnnouncementId == announcement.id) {
      return;
    }
    _shownAnnouncementId = announcement.id;
    WidgetsBinding.instance.addPostFrameCallback((_) async {
      if (!mounted) {
        return;
      }
      final acknowledged = await showHomeAnnouncement(
        context,
        announcement,
        onAction: _onAnnouncementAction,
      );
      if (acknowledged == true) {
        await ref.read(homeControllerProvider.notifier).dismissAnnouncement(announcement.id);
      }
    });
  }

  void _onAnnouncementAction(HomeAnnouncement announcement) {
    _showComingSoon();
  }

  void _onAction(String actionKey) {
    _showComingSoon();
  }

  void _onBannerTap(HomeBanner banner) {
    _showComingSoon();
  }

  void _showComingSoon() {
    final s = HomeStrings.of(context);
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text(s.comingSoon)));
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(homeControllerProvider);

    ref.listen(homeControllerProvider, (_, next) {
      final data = next.valueOrNull;
      if (data != null) {
        _maybeShowAnnouncement(data);
      }
    });

    return AppScaffold(
      appBar: AppBar(
        title: Text(context.l10n.appName),
        actions: [
          UnreadBadge(
            child: IconButton(
              icon: const Icon(Icons.notifications_outlined),
              tooltip: NotificationStrings.of(context).title,
              onPressed: () => context.push('/notifications'),
            ),
          ),
          const SizedBox(width: AppSpacing.xs),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _refresh,
        child: switch (state) {
          AsyncData(:final value) => _Dashboard(
              data: value,
              onAction: _onAction,
              onBannerTap: _onBannerTap,
            ),
          AsyncError(:final error) => _ErrorState(error: error, onRetry: _refresh),
          _ => const HomeSkeleton(),
        },
      ),
    );
  }
}

class _Dashboard extends StatelessWidget {
  const _Dashboard({
    required this.data,
    required this.onAction,
    required this.onBannerTap,
  });

  final HomeData data;
  final void Function(String actionKey) onAction;
  final void Function(HomeBanner banner) onBannerTap;

  @override
  Widget build(BuildContext context) {
    final content = ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(AppSpacing.screen),
      children: [
        ProfileHeader(user: data.user),
        const SizedBox(height: AppSpacing.xl),
        BalanceCard(balance: data.balance),
        const SizedBox(height: AppSpacing.xl),
        ..._sections(context),
        DailyCheckinCard(onTap: () => onAction('checkin')),
        const SizedBox(height: AppSpacing.lg),
        RecentTransactionsPreview(
          transactions: data.recentTransactions,
          onViewAll: () => onAction('wallet'),
        ),
        const SizedBox(height: AppSpacing.lg),
        ReferralCard(onTap: () => onAction('refer')),
      ],
    );

    // Responsive: constrain width on larger screens for readability.
    return ResponsiveLayout(
      phone: (context) => content,
      tablet: (context) => Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 640),
          child: content,
        ),
      ),
    );
  }

  List<Widget> _sections(BuildContext context) {
    if (data.sections.isEmpty) {
      return [
        Padding(
          padding: const EdgeInsets.only(bottom: AppSpacing.xl),
          child: EmptyView(
            icon: Icons.dashboard_customize_outlined,
            title: HomeStrings.of(context).comingSoon,
            message: HomeStrings.of(context).quickActions,
          ),
        ),
      ];
    }
    final widgets = <Widget>[];
    for (final section in data.sections) {
      final rendered = SectionRenderer(
        section: section,
        banners: data.banners,
        onAction: onAction,
        onBannerTap: onBannerTap,
      );
      widgets
        ..add(rendered)
        ..add(const SizedBox(height: AppSpacing.xl));
    }
    return widgets;
  }
}

class _ErrorState extends StatelessWidget {
  const _ErrorState({required this.error, required this.onRetry});

  final Object error;
  final Future<void> Function() onRetry;

  @override
  Widget build(BuildContext context) {
    final s = HomeStrings.of(context);
    final err = error;
    final message = err is AppException ? err.message : s.errorTitle;

    // Scrollable so pull-to-refresh works even in the error state.
    return LayoutBuilder(
      builder: (context, constraints) => SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        child: ConstrainedBox(
          constraints: BoxConstraints(minHeight: constraints.maxHeight),
          child: ErrorView(message: message, onRetry: onRetry),
        ),
      ),
    );
  }
}

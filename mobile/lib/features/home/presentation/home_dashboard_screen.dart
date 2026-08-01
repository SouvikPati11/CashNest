import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../app/router/app_route_paths.dart';
import '../../../core/error/app_exception.dart';
import '../../../core/responsive/responsive.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../shared/extensions/context_extensions.dart';
import '../../../shared/widgets/app_scaffold.dart';
import '../../../shared/widgets/error_view.dart';
import '../../../shared/widgets/fade_slide_in.dart';
import '../../notification/l10n/notification_strings.dart';
import '../../notification/presentation/widgets/unread_badge.dart';
import '../l10n/home_strings.dart';
import '../models/home_announcement.dart';
import '../models/home_banner.dart';
import '../models/home_data.dart';
import '../providers/home_providers.dart';
import '../../rewards/presentation/daily_checkin_screen.dart';
import '../../rewards/presentation/scratch_card_screen.dart';
import '../../rewards/presentation/spin_wheel_screen.dart';
import '../../rewards/presentation/tasks_screen.dart';
import 'widgets/announcement_popup.dart';
import 'widgets/balance_card.dart';
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
    _handleContentAction(announcement.actionType, announcement.actionValue);
  }

  void _onAction(String actionKey) {
    switch (actionKey) {
      case 'checkin':
        _push(const DailyCheckinScreen());
        break;
      case 'scratch':
        _push(const ScratchCardScreen());
        break;
      case 'spin':
        _push(const SpinWheelScreen());
        break;
      case 'tasks':
        _push(const TasksScreen());
        break;
      case 'offers':
        context.go(AppRoutePaths.earn);
        break;
      case 'wallet':
        context.go(AppRoutePaths.wallet);
        break;
      case 'rewards':
        context.go(AppRoutePaths.rewards);
        break;
      case 'refer':
        context.push(AppRoutePaths.referral);
        break;
      case 'withdraw':
        context.push(AppRoutePaths.withdraw);
        break;
      default:
        // Unknown action keys are non-interactive (no placeholder shown).
        break;
    }
  }

  void _push(Widget screen) {
    Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => screen));
  }

  void _onBannerTap(HomeBanner banner) {
    _handleContentAction(banner.actionType, banner.actionValue);
  }

  /// Routes a server-driven banner/announcement action to an in-app
  /// destination. External URLs ('url') are intentionally inert until external
  /// linking is enabled; 'none'/unknown types are non-interactive.
  void _handleContentAction(String actionType, String? actionValue) {
    switch (actionType) {
      case 'deep_link':
        if (actionValue != null && actionValue.startsWith('/')) {
          context.push(actionValue);
        }
        break;
      case 'offer':
        context.go(AppRoutePaths.earn);
        break;
      case 'task':
        _push(const TasksScreen());
        break;
      default:
        break;
    }
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
    var step = 0;
    Widget animated(Widget child) {
      final widget = FadeSlideIn(
        delay: Duration(milliseconds: 60 * step),
        child: child,
      );
      step++;
      return widget;
    }

    final content = ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.fromLTRB(
        AppSpacing.screen,
        AppSpacing.lg,
        AppSpacing.screen,
        AppSpacing.xxl,
      ),
      children: [
        animated(ProfileHeader(user: data.user)),
        const SizedBox(height: AppSpacing.xl),
        animated(BalanceCard(balance: data.balance)),
        const SizedBox(height: AppSpacing.section),
        // Server-driven layout: sections are defined and ordered in the Admin
        // Panel (home_sections) and rendered here via SectionRenderer.
        for (final section in _sections()) ...[
          animated(section),
          const SizedBox(height: AppSpacing.section),
        ],
        animated(RecentTransactionsPreview(
          transactions: data.recentTransactions,
          onViewAll: () => onAction('wallet'),
        )),
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

  /// Server-driven home sections (defined/ordered in the Admin Panel). Unknown
  /// section types render nothing; an empty layout simply shows no sections.
  List<Widget> _sections() {
    return [
      for (final section in data.sections)
        SectionRenderer(
          section: section,
          banners: data.banners,
          onAction: onAction,
          onBannerTap: onBannerTap,
        ),
    ];
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

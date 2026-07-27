import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/error/app_exception.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../shared/extensions/context_extensions.dart';
import '../../../shared/widgets/app_scaffold.dart';
import '../../offerwall/presentation/widgets/list_skeleton.dart';
import '../l10n/referral_strings.dart';
import '../providers/referral_providers.dart';
import 'widgets/apply_code_dialog.dart';
import 'widgets/earnings_tab.dart';
import 'widgets/leaderboard_tab.dart';
import 'widgets/referral_info_card.dart';
import 'widgets/referrals_tab.dart';

/// Refer & earn hub: referral info + sharing, then Referrals / Earnings /
/// Leaderboard tabs.
class ReferralScreen extends ConsumerStatefulWidget {
  const ReferralScreen({super.key});

  @override
  ConsumerState<ReferralScreen> createState() => _ReferralScreenState();
}

class _ReferralScreenState extends ConsumerState<ReferralScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(referralInfoControllerProvider.notifier).load();
    });
  }

  Future<void> _openApplyDialog() async {
    await showApplyCodeDialog(context);
  }

  @override
  Widget build(BuildContext context) {
    final s = ReferralStrings.of(context);
    final info = ref.watch(referralInfoControllerProvider);

    return DefaultTabController(
      length: 3,
      child: AppScaffold(
        appBar: AppBar(
          title: Text(s.title),
          bottom: TabBar(
            isScrollable: true,
            tabs: [
              Tab(text: s.tabReferrals),
              Tab(text: s.tabEarnings),
              Tab(text: s.tabLeaderboard),
            ],
          ),
        ),
        body: Column(
          children: [
            switch (info) {
              AsyncData(:final value) =>
                ReferralInfoCard(info: value, onApplyCode: _openApplyDialog),
              AsyncError(:final error) => _InfoError(
                  error: error,
                  onRetry: () => ref.read(referralInfoControllerProvider.notifier).load(),
                ),
              _ => const Padding(
                  padding: EdgeInsets.all(AppSpacing.screen),
                  child: SkeletonBox(width: double.infinity, height: 180),
                ),
            },
            const Divider(height: 1),
            const Expanded(
              child: TabBarView(
                children: [
                  ReferralsTab(),
                  EarningsTab(),
                  LeaderboardTab(),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _InfoError extends StatelessWidget {
  const _InfoError({required this.error, required this.onRetry});

  final Object error;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    final s = ReferralStrings.of(context);
    final err = error;
    return Padding(
      padding: const EdgeInsets.all(AppSpacing.screen),
      child: Row(
        children: [
          Expanded(
            child: Text(
              err is AppException ? err.message : s.errorTitle,
              style: context.textTheme.bodyMedium?.copyWith(color: context.colors.error),
            ),
          ),
          TextButton(onPressed: onRetry, child: Text(context.l10n.retry)),
        ],
      ),
    );
  }
}

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/theme/app_radius.dart';
import '../../../../core/theme/app_spacing.dart';
import '../../../../shared/extensions/context_extensions.dart';
import '../../../offerwall/presentation/widgets/paginated_list_view.dart';
import '../../../offerwall/presentation/widgets/search_field.dart';
import '../../../offerwall/resources/offer_formatters.dart';
import '../../l10n/referral_strings.dart';
import '../../models/leaderboard_entry.dart';
import '../../providers/referral_providers.dart';

/// The "Leaderboard" tab: period selector, the caller's rank, and a searchable
/// paginated ranking list.
class LeaderboardTab extends ConsumerStatefulWidget {
  const LeaderboardTab({super.key});

  @override
  ConsumerState<LeaderboardTab> createState() => _LeaderboardTabState();
}

class _LeaderboardTabState extends ConsumerState<LeaderboardTab>
    with AutomaticKeepAliveClientMixin {
  String _period = 'weekly';

  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(leaderboardControllerProvider.notifier).load();
    });
  }

  void _setPeriod(String period) {
    if (_period == period) {
      return;
    }
    setState(() => _period = period);
    ref.read(leaderboardControllerProvider.notifier).setPeriod(period);
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);
    final s = ReferralStrings.of(context);
    final state = ref.watch(leaderboardControllerProvider);
    final controller = ref.read(leaderboardControllerProvider.notifier);
    final myRank = ref.watch(myRankProvider(_period));

    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(
            AppSpacing.screen,
            AppSpacing.md,
            AppSpacing.screen,
            AppSpacing.sm,
          ),
          child: SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: SegmentedButton<String>(
              segments: [
                ButtonSegment(value: 'daily', label: Text(s.periodDaily)),
                ButtonSegment(value: 'weekly', label: Text(s.periodWeekly)),
                ButtonSegment(value: 'monthly', label: Text(s.periodMonthly)),
                ButtonSegment(value: 'all_time', label: Text(s.periodAllTime)),
              ],
              selected: {_period},
              showSelectedIcon: false,
              onSelectionChanged: (selection) => _setPeriod(selection.first),
            ),
          ),
        ),
        myRank.maybeWhen(
          data: (me) => _MyRankBanner(rank: me.rank, score: me.score),
          orElse: () => const SizedBox.shrink(),
        ),
        Padding(
          padding: const EdgeInsets.fromLTRB(
            AppSpacing.screen,
            AppSpacing.sm,
            AppSpacing.screen,
            AppSpacing.sm,
          ),
          child: SearchField(
            hintText: s.searchHint,
            initialValue: state.searchQuery,
            onChanged: controller.setSearch,
          ),
        ),
        Expanded(
          child: PaginatedListView<LeaderboardEntry>(
            state: state,
            visibleItems: controller.visibleItems,
            isFiltering: state.hasSearch,
            onRefresh: controller.refresh,
            onLoadMore: controller.loadMore,
            onRetryLoadMore: controller.retryLoadMore,
            onRetry: controller.load,
            emptyIcon: Icons.leaderboard_outlined,
            emptyTitle: s.noLeaderboard,
            emptyBody: s.noLeaderboardBody,
            noResultsTitle: s.noResults,
            noResultsBody: s.noResultsBody,
            loadMoreErrorText: s.loadMoreError,
            skeletonItemHeight: 64,
            itemBuilder: (context, entry) => _RankRow(entry: entry),
          ),
        ),
      ],
    );
  }
}

class _MyRankBanner extends StatelessWidget {
  const _MyRankBanner({required this.rank, required this.score});

  final int rank;
  final int score;

  @override
  Widget build(BuildContext context) {
    final s = ReferralStrings.of(context);
    final localeCode = Localizations.localeOf(context).languageCode;
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: AppSpacing.screen),
      padding: const EdgeInsets.all(AppSpacing.md),
      decoration: BoxDecoration(
        color: context.colors.primaryContainer,
        borderRadius: AppRadius.mdAll,
      ),
      child: Row(
        children: [
          Icon(Icons.emoji_events, color: context.colors.onPrimaryContainer),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Text(
              s.yourRank,
              style: context.textTheme.titleSmall?.copyWith(color: context.colors.onPrimaryContainer),
            ),
          ),
          Text(
            rank > 0 ? '#$rank · ${OfferFormatters.coins(score, localeCode: localeCode)} ${s.points}' : s.notRanked,
            style: context.textTheme.titleSmall?.copyWith(
              color: context.colors.onPrimaryContainer,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }
}

class _RankRow extends StatelessWidget {
  const _RankRow({required this.entry});

  final LeaderboardEntry entry;

  @override
  Widget build(BuildContext context) {
    final s = ReferralStrings.of(context);
    final localeCode = Localizations.localeOf(context).languageCode;
    final isTop = entry.rank >= 1 && entry.rank <= 3;

    return Card(
      child: ListTile(
        shape: const RoundedRectangleBorder(borderRadius: AppRadius.mdAll),
        leading: SizedBox(
          width: 64,
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              SizedBox(
                width: 24,
                child: Text(
                  '${entry.rank}',
                  textAlign: TextAlign.center,
                  style: context.textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w800,
                    color: isTop ? context.colors.tertiary : context.colors.onSurfaceVariant,
                  ),
                ),
              ),
              const SizedBox(width: AppSpacing.xs),
              CircleAvatar(
                radius: 16,
                backgroundColor: context.colors.surfaceContainerHighest,
                foregroundImage: (entry.avatarUrl != null && entry.avatarUrl!.isNotEmpty)
                    ? NetworkImage(entry.avatarUrl!)
                    : null,
                child: Text(
                  entry.name.isNotEmpty ? entry.name.characters.first.toUpperCase() : '?',
                  style: context.textTheme.labelMedium,
                ),
              ),
            ],
          ),
        ),
        title: Text(entry.name, maxLines: 1, overflow: TextOverflow.ellipsis),
        trailing: Text(
          '${OfferFormatters.coins(entry.score, localeCode: localeCode)} ${s.points}',
          style: context.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w700),
        ),
      ),
    );
  }
}

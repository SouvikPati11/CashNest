import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/theme/app_radius.dart';
import '../../../../core/theme/app_spacing.dart';
import '../../../../shared/extensions/context_extensions.dart';
import '../../../offerwall/presentation/widgets/paginated_list_view.dart';
import '../../../offerwall/presentation/widgets/search_field.dart';
import '../../../offerwall/resources/offer_formatters.dart';
import '../../l10n/referral_strings.dart';
import '../../models/referral_entry.dart';
import '../../providers/referral_providers.dart';

/// The "Referrals" tab: searchable, status-filterable list of referred users.
class ReferralsTab extends ConsumerStatefulWidget {
  const ReferralsTab({super.key});

  @override
  ConsumerState<ReferralsTab> createState() => _ReferralsTabState();
}

class _ReferralsTabState extends ConsumerState<ReferralsTab>
    with AutomaticKeepAliveClientMixin {
  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(referralListControllerProvider.notifier).load();
    });
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);
    final s = ReferralStrings.of(context);
    final state = ref.watch(referralListControllerProvider);
    final controller = ref.read(referralListControllerProvider.notifier);

    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(
            AppSpacing.screen,
            AppSpacing.md,
            AppSpacing.screen,
            AppSpacing.sm,
          ),
          child: SearchField(
            hintText: s.searchHint,
            initialValue: state.searchQuery,
            onChanged: controller.setSearch,
          ),
        ),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: AppSpacing.screen),
          child: Row(
            children: [
              _StatusChip(label: s.all, selected: controller.status == null, onTap: () => controller.applyStatus(null)),
              const SizedBox(width: AppSpacing.sm),
              _StatusChip(
                label: s.statusQualified,
                selected: controller.status == 'qualified',
                onTap: () => controller.applyStatus('qualified'),
              ),
              const SizedBox(width: AppSpacing.sm),
              _StatusChip(
                label: s.statusPending,
                selected: controller.status == 'pending',
                onTap: () => controller.applyStatus('pending'),
              ),
            ],
          ),
        ),
        const SizedBox(height: AppSpacing.sm),
        Expanded(
          child: PaginatedListView<ReferralEntry>(
            state: state,
            visibleItems: controller.visibleItems,
            isFiltering: state.hasSearch || controller.status != null,
            onRefresh: controller.refresh,
            onLoadMore: controller.loadMore,
            onRetryLoadMore: controller.retryLoadMore,
            onRetry: controller.load,
            emptyIcon: Icons.group_outlined,
            emptyTitle: s.noReferrals,
            emptyBody: s.noReferralsBody,
            noResultsTitle: s.noResults,
            noResultsBody: s.noResultsBody,
            loadMoreErrorText: s.loadMoreError,
            skeletonItemHeight: 64,
            itemBuilder: (context, entry) => _ReferralRow(entry: entry),
          ),
        ),
      ],
    );
  }
}

class _StatusChip extends StatelessWidget {
  const _StatusChip({required this.label, required this.selected, required this.onTap});

  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return ChoiceChip(label: Text(label), selected: selected, onSelected: (_) => onTap());
  }
}

class _ReferralRow extends StatelessWidget {
  const _ReferralRow({required this.entry});

  final ReferralEntry entry;

  @override
  Widget build(BuildContext context) {
    final s = ReferralStrings.of(context);
    final qualified = entry.isQualified;
    final color = qualified ? Colors.green.shade600 : context.colors.onSurfaceVariant;

    return Card(
      child: ListTile(
        shape: const RoundedRectangleBorder(borderRadius: AppRadius.mdAll),
        leading: CircleAvatar(
          backgroundColor: context.colors.secondaryContainer,
          child: Text(
            entry.refereeName.isNotEmpty ? entry.refereeName.characters.first.toUpperCase() : '?',
            style: TextStyle(color: context.colors.onSecondaryContainer),
          ),
        ),
        title: Text(entry.refereeName, maxLines: 1, overflow: TextOverflow.ellipsis),
        subtitle: entry.joinedAt != null
            ? Text('${s.joined} ${OfferFormatters.dateTime(entry.joinedAt!)}')
            : null,
        trailing: Chip(
          label: Text(s.statusLabel(entry.status)),
          labelStyle: context.textTheme.labelSmall?.copyWith(color: color),
          visualDensity: VisualDensity.compact,
        ),
      ),
    );
  }
}

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/theme/app_radius.dart';
import '../../../../shared/extensions/context_extensions.dart';
import '../../../offerwall/presentation/widgets/paginated_list_view.dart';
import '../../../offerwall/resources/offer_formatters.dart';
import '../../l10n/referral_strings.dart';
import '../../models/referral_earning.dart';
import '../../providers/referral_providers.dart';

/// The "Earnings" tab: paginated referral commission history.
class EarningsTab extends ConsumerStatefulWidget {
  const EarningsTab({super.key});

  @override
  ConsumerState<EarningsTab> createState() => _EarningsTabState();
}

class _EarningsTabState extends ConsumerState<EarningsTab>
    with AutomaticKeepAliveClientMixin {
  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(earningsControllerProvider.notifier).load();
    });
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);
    final s = ReferralStrings.of(context);
    final state = ref.watch(earningsControllerProvider);
    final controller = ref.read(earningsControllerProvider.notifier);
    final localeCode = Localizations.localeOf(context).languageCode;

    return PaginatedListView<ReferralEarning>(
      state: state,
      visibleItems: controller.visibleItems,
      onRefresh: controller.refresh,
      onLoadMore: controller.loadMore,
      onRetryLoadMore: controller.retryLoadMore,
      onRetry: controller.load,
      emptyIcon: Icons.payments_outlined,
      emptyTitle: s.noEarnings,
      emptyBody: s.noEarningsBody,
      noResultsTitle: s.noResults,
      noResultsBody: s.noResultsBody,
      loadMoreErrorText: s.loadMoreError,
      skeletonItemHeight: 64,
      itemBuilder: (context, earning) => Card(
        child: ListTile(
          shape: const RoundedRectangleBorder(borderRadius: AppRadius.mdAll),
          leading: CircleAvatar(
            backgroundColor: context.colors.primaryContainer,
            child: Icon(Icons.group, color: context.colors.onPrimaryContainer, size: 20),
          ),
          title: Text(earning.source ?? s.commission, maxLines: 1, overflow: TextOverflow.ellipsis),
          subtitle:
              earning.createdAt != null ? Text(OfferFormatters.dateTime(earning.createdAt!)) : null,
          trailing: Text(
            '+${OfferFormatters.coins(earning.commissionCoins, localeCode: localeCode)}',
            style: context.textTheme.titleMedium?.copyWith(
              color: context.colors.primary,
              fontWeight: FontWeight.w700,
            ),
          ),
        ),
      ),
    );
  }
}

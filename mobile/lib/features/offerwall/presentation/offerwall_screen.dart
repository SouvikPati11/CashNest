import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_radius.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../shared/extensions/context_extensions.dart';
import '../../../shared/widgets/app_scaffold.dart';
import '../application/offers_controller.dart';
import '../l10n/offerwall_strings.dart';
import '../models/offer.dart';
import '../providers/offerwall_providers.dart';
import '../resources/offer_formatters.dart';
import 'offer_detail_screen.dart';
import 'widgets/earn_hub_tab.dart';
import 'widgets/offer_filter_sheet.dart';
import 'widgets/offer_tile.dart';
import 'widgets/paginated_list_view.dart';
import 'widgets/search_field.dart';

/// Offerwall hub with Offers, CPA, and History tabs.
///
/// The Offers/CPA tabs surface third-party offers and can be disabled from the
/// Admin Panel via the `offerwall_enabled` remote-config flag; when disabled the
/// Earn hub and History remain available. Fail-open: the tabs stay visible while
/// the flag loads and whenever it is unavailable.
class OfferwallScreen extends ConsumerWidget {
  const OfferwallScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = OfferwallStrings.of(context);
    final offerwallEnabled = ref.watch(offerwallEnabledProvider).valueOrNull ?? true;

    final tabs = <Tab>[
      const Tab(text: 'Earn'),
      if (offerwallEnabled) ...[
        Tab(text: s.tabOffers),
        Tab(text: s.tabCpa),
      ],
      Tab(text: s.tabHistory),
    ];
    final views = <Widget>[
      const EarnHubTab(),
      if (offerwallEnabled) ...const [
        _OffersTab(source: OfferSource.offerwall),
        _OffersTab(source: OfferSource.cpa),
      ],
      const _HistoryTab(),
    ];

    return DefaultTabController(
      length: tabs.length,
      child: AppScaffold(
        appBar: AppBar(
          title: Text(s.title),
          bottom: TabBar(
            isScrollable: true,
            tabAlignment: TabAlignment.start,
            tabs: tabs,
          ),
        ),
        body: TabBarView(children: views),
      ),
    );
  }
}

class _OffersTab extends ConsumerStatefulWidget {
  const _OffersTab({required this.source});

  final OfferSource source;

  @override
  ConsumerState<_OffersTab> createState() => _OffersTabState();
}

class _OffersTabState extends ConsumerState<_OffersTab> with AutomaticKeepAliveClientMixin {
  bool get _isCpa => widget.source == OfferSource.cpa;

  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _notifier.load();
      // Warm the providers list so filter chips are ready when opened.
      ref.read(offerwallProvidersListProvider);
    });
  }

  OffersController get _notifier => _isCpa
      ? ref.read(cpaOffersControllerProvider.notifier)
      : ref.read(offersControllerProvider.notifier);

  Future<void> _openFilters() async {
    final providers = ref.read(offerwallProvidersListProvider).valueOrNull ?? const [];
    final updated = await showOfferFilterSheet(
      context,
      _notifier.filter,
      providers: providers,
      showCategory: !_isCpa,
      showCountry: _isCpa,
    );
    if (updated != null) {
      await _notifier.applyFilter(updated);
    }
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);
    final s = OfferwallStrings.of(context);
    final state = _isCpa
        ? ref.watch(cpaOffersControllerProvider)
        : ref.watch(offersControllerProvider);
    final controller = _notifier;
    final activeFilters = controller.filter.activeCount;

    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(
            AppSpacing.screen,
            AppSpacing.md,
            AppSpacing.screen,
            AppSpacing.sm,
          ),
          child: Row(
            children: [
              Expanded(
                child: SearchField(
                  hintText: s.searchHint,
                  initialValue: state.searchQuery,
                  onChanged: controller.setSearch,
                ),
              ),
              const SizedBox(width: AppSpacing.sm),
              IconButton.filledTonal(
                onPressed: _openFilters,
                icon: Badge(
                  isLabelVisible: activeFilters > 0,
                  label: Text('$activeFilters'),
                  child: const Icon(Icons.tune),
                ),
              ),
            ],
          ),
        ),
        Expanded(
          child: PaginatedListView<Offer>(
            state: state,
            visibleItems: controller.visibleItems,
            isFiltering: state.hasSearch || controller.filter.isActive,
            onRefresh: controller.refresh,
            onLoadMore: controller.loadMore,
            onRetryLoadMore: controller.retryLoadMore,
            onRetry: controller.load,
            emptyIcon: Icons.local_offer_outlined,
            emptyTitle: s.noOffers,
            emptyBody: s.noOffersBody,
            noResultsTitle: s.noResults,
            noResultsBody: s.noResultsBody,
            loadMoreErrorText: s.loadMoreError,
            itemBuilder: (context, offer) => OfferTile(
              offer: offer,
              onTap: () => Navigator.of(context).push(
                MaterialPageRoute<void>(builder: (_) => OfferDetailScreen(offer: offer)),
              ),
            ),
          ),
        ),
      ],
    );
  }
}

class _HistoryTab extends ConsumerStatefulWidget {
  const _HistoryTab();

  @override
  ConsumerState<_HistoryTab> createState() => _HistoryTabState();
}

class _HistoryTabState extends ConsumerState<_HistoryTab> with AutomaticKeepAliveClientMixin {
  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(offerwallHistoryControllerProvider.notifier).load();
    });
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);
    final s = OfferwallStrings.of(context);
    final state = ref.watch(offerwallHistoryControllerProvider);
    final controller = ref.read(offerwallHistoryControllerProvider.notifier);
    final localeCode = Localizations.localeOf(context).languageCode;

    return PaginatedListView(
      state: state,
      visibleItems: controller.visibleItems,
      onRefresh: controller.refresh,
      onLoadMore: controller.loadMore,
      onRetryLoadMore: controller.retryLoadMore,
      onRetry: controller.load,
      emptyIcon: Icons.receipt_long_outlined,
      emptyTitle: s.noHistory,
      emptyBody: s.noHistoryBody,
      noResultsTitle: s.noResults,
      noResultsBody: s.noResultsBody,
      loadMoreErrorText: s.loadMoreError,
      skeletonItemHeight: 64,
      itemBuilder: (context, entry) => Card(
        child: ListTile(
          leading: CircleAvatar(
            backgroundColor: context.colors.primaryContainer,
            child: Icon(Icons.local_offer, color: context.colors.onPrimaryContainer, size: 20),
          ),
          title: Text(
            entry.description ?? entry.type,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
          subtitle: entry.createdAt != null ? Text(OfferFormatters.dateTime(entry.createdAt!)) : null,
          trailing: Text(
            '+${OfferFormatters.coins(entry.coins, localeCode: localeCode)}',
            style: context.textTheme.titleMedium?.copyWith(
              color: context.colors.primary,
              fontWeight: FontWeight.w700,
            ),
          ),
          shape: const RoundedRectangleBorder(borderRadius: AppRadius.mdAll),
        ),
      ),
    );
  }
}

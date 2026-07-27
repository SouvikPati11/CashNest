import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/error/app_exception.dart';
import '../../../core/responsive/responsive.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../shared/extensions/context_extensions.dart';
import '../../../shared/widgets/app_scaffold.dart';
import '../../../shared/widgets/empty_view.dart';
import '../../../shared/widgets/error_view.dart';
import '../application/transactions_state.dart';
import '../l10n/wallet_strings.dart';
import '../models/wallet_overview.dart';
import '../models/wallet_transaction.dart';
import '../providers/wallet_providers.dart';
import 'transaction_detail_screen.dart';
import 'widgets/balance_summary_card.dart';
import 'widgets/conversion_card.dart';
import 'widgets/transaction_filter_sheet.dart';
import 'widgets/transaction_search_bar.dart';
import 'widgets/transaction_tile.dart';
import 'widgets/wallet_skeleton.dart';

/// The wallet screen: balance summary + conversion header, followed by the
/// filterable, searchable, infinitely-scrolling transaction history.
class WalletScreen extends ConsumerStatefulWidget {
  const WalletScreen({super.key});

  @override
  ConsumerState<WalletScreen> createState() => _WalletScreenState();
}

class _WalletScreenState extends ConsumerState<WalletScreen> {
  final ScrollController _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(walletSummaryControllerProvider.notifier).load();
      ref.read(transactionsControllerProvider.notifier).load();
    });
  }

  @override
  void dispose() {
    _scrollController
      ..removeListener(_onScroll)
      ..dispose();
    super.dispose();
  }

  void _onScroll() {
    if (!_scrollController.hasClients) {
      return;
    }
    final position = _scrollController.position;
    if (position.pixels >= position.maxScrollExtent - 320) {
      ref.read(transactionsControllerProvider.notifier).loadMore();
    }
  }

  Future<void> _refreshAll() async {
    await Future.wait([
      ref.read(walletSummaryControllerProvider.notifier).refresh(),
      ref.read(transactionsControllerProvider.notifier).refresh(),
    ]);
  }

  Future<void> _openFilters() async {
    final current = ref.read(transactionsControllerProvider).filter;
    final updated = await showTransactionFilterSheet(context, current);
    if (updated != null) {
      await ref.read(transactionsControllerProvider.notifier).applyFilter(updated);
    }
  }

  void _openDetail(WalletTransaction transaction) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => TransactionDetailScreen(uuid: transaction.uuid),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final s = WalletStrings.of(context);
    final summary = ref.watch(walletSummaryControllerProvider);
    final txState = ref.watch(transactionsControllerProvider);
    final activeFilters = txState.filter.activeCount;

    return AppScaffold(
      appBar: AppBar(
        title: Text(s.title),
        actions: [
          IconButton(
            tooltip: s.filters,
            onPressed: _openFilters,
            icon: Badge(
              isLabelVisible: activeFilters > 0,
              label: Text('$activeFilters'),
              child: const Icon(Icons.tune),
            ),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _refreshAll,
        child: _buildBody(context, summary, txState),
      ),
    );
  }

  Widget _buildBody(
    BuildContext context,
    AsyncValue<WalletOverview> summary,
    TransactionsState txState,
  ) {
    // Full-screen skeleton until the required summary resolves the first time.
    if (summary.isLoading && !summary.hasValue) {
      return const WalletSkeleton();
    }

    if (summary.hasError && !summary.hasValue) {
      final error = summary.error;
      return _ScrollableError(
        message: error is AppException ? error.message : WalletStrings.of(context).errorTitle,
        onRetry: () => ref.read(walletSummaryControllerProvider.notifier).load(),
      );
    }

    final overview = summary.value!;
    final content = CustomScrollView(
      controller: _scrollController,
      physics: const AlwaysScrollableScrollPhysics(),
      slivers: _slivers(context, overview, txState),
    );

    return ResponsiveLayout(
      phone: (context) => content,
      tablet: (context) => Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 720),
          child: content,
        ),
      ),
    );
  }

  List<Widget> _slivers(
    BuildContext context,
    WalletOverview overview,
    TransactionsState txState,
  ) {
    final s = WalletStrings.of(context);

    return [
      SliverPadding(
        padding: const EdgeInsets.fromLTRB(
          AppSpacing.screen,
          AppSpacing.screen,
          AppSpacing.screen,
          0,
        ),
        sliver: SliverList.list(
          children: [
            BalanceSummaryCard(summary: overview.summary),
            if (overview.conversion != null) ...[
              const SizedBox(height: AppSpacing.lg),
              ConversionCard(conversion: overview.conversion!),
            ],
            const SizedBox(height: AppSpacing.xl),
            Text(s.transactionHistory, style: context.textTheme.titleLarge),
            const SizedBox(height: AppSpacing.md),
            TransactionSearchBar(
              initialValue: txState.searchQuery,
              onChanged: (q) =>
                  ref.read(transactionsControllerProvider.notifier).setSearch(q),
            ),
            const SizedBox(height: AppSpacing.sm),
          ],
        ),
      ),
      ..._transactionSlivers(context, txState),
    ];
  }

  List<Widget> _transactionSlivers(BuildContext context, TransactionsState txState) {
    final s = WalletStrings.of(context);

    // First-page load: inline skeleton rows.
    if (txState.isFirstLoad) {
      return [
        SliverPadding(
          padding: const EdgeInsets.symmetric(horizontal: AppSpacing.screen),
          sliver: SliverList.builder(
            itemCount: 6,
            itemBuilder: (_, __) => const TransactionTileSkeleton(),
          ),
        ),
      ];
    }

    // First-page error (no items to show).
    if (txState.status == TransactionsStatus.error) {
      final error = txState.error;
      return [
        SliverToBoxAdapter(
          child: Padding(
            padding: const EdgeInsets.all(AppSpacing.xl),
            child: ErrorView(
              message: error is AppException ? error.message : s.errorTitle,
              onRetry: () => ref.read(transactionsControllerProvider.notifier).load(),
            ),
          ),
        ),
      ];
    }

    final items = txState.visibleItems;
    if (items.isEmpty) {
      final filtering = txState.hasSearch || txState.filter.isActive;
      return [
        SliverToBoxAdapter(
          child: Padding(
            padding: const EdgeInsets.all(AppSpacing.xl),
            child: EmptyView(
              icon: filtering ? Icons.search_off : Icons.receipt_long_outlined,
              title: filtering ? s.noResults : s.noTransactions,
              message: filtering ? s.noResultsBody : s.noTransactionsBody,
              action: filtering
                  ? OutlinedButton(
                      onPressed: () {
                        ref.read(transactionsControllerProvider.notifier)
                          ..setSearch('')
                          ..clearFilter();
                      },
                      child: Text(s.clearFilters),
                    )
                  : null,
            ),
          ),
        ),
      ];
    }

    return [
      SliverPadding(
        padding: const EdgeInsets.symmetric(horizontal: AppSpacing.screen),
        sliver: SliverList.separated(
          itemCount: items.length,
          itemBuilder: (context, index) => TransactionTile(
            transaction: items[index],
            onTap: () => _openDetail(items[index]),
          ),
          separatorBuilder: (_, __) => Divider(
            height: 1,
            color: context.colors.outlineVariant.withValues(alpha: 0.5),
          ),
        ),
      ),
      SliverToBoxAdapter(child: _Footer(txState: txState, onRetry: _retryLoadMore)),
    ];
  }

  void _retryLoadMore() {
    ref.read(transactionsControllerProvider.notifier).retryLoadMore();
  }
}

class _Footer extends StatelessWidget {
  const _Footer({required this.txState, required this.onRetry});

  final TransactionsState txState;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    final s = WalletStrings.of(context);

    if (txState.isLoadingMore) {
      return const Padding(
        padding: EdgeInsets.all(AppSpacing.lg),
        child: Center(child: CircularProgressIndicator()),
      );
    }

    if (txState.loadMoreError != null) {
      return Padding(
        padding: const EdgeInsets.all(AppSpacing.lg),
        child: Center(
          child: TextButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh),
            label: Text(s.loadMoreError),
          ),
        ),
      );
    }

    // Bottom breathing room when the whole list is loaded.
    return const SizedBox(height: AppSpacing.xxl);
  }
}

class _ScrollableError extends StatelessWidget {
  const _ScrollableError({required this.message, required this.onRetry});

  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
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

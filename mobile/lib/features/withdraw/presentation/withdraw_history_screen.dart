import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_spacing.dart';
import '../../../shared/widgets/app_scaffold.dart';
import '../../offerwall/presentation/widgets/paginated_list_view.dart';
import '../l10n/withdraw_strings.dart';
import '../models/withdraw_request.dart';
import '../providers/withdraw_providers.dart';
import 'withdraw_detail_screen.dart';
import 'widgets/withdraw_request_tile.dart';

/// Paginated withdrawal history with a status filter.
class WithdrawHistoryScreen extends ConsumerStatefulWidget {
  const WithdrawHistoryScreen({super.key});

  @override
  ConsumerState<WithdrawHistoryScreen> createState() => _WithdrawHistoryScreenState();
}

class _WithdrawHistoryScreenState extends ConsumerState<WithdrawHistoryScreen> {
  static const List<String> _statuses = ['pending', 'processing', 'paid', 'cancelled'];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(withdrawHistoryControllerProvider.notifier).load();
    });
  }

  @override
  Widget build(BuildContext context) {
    final s = WithdrawStrings.of(context);
    final state = ref.watch(withdrawHistoryControllerProvider);
    final controller = ref.read(withdrawHistoryControllerProvider.notifier);

    return AppScaffold(
      appBar: AppBar(title: Text(s.historyTitle)),
      body: Column(
        children: [
          SizedBox(
            height: 52,
            child: ListView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: AppSpacing.screen, vertical: AppSpacing.sm),
              children: [
                _FilterChip(label: s.all, selected: controller.status == null, onTap: () => controller.applyStatus(null)),
                for (final status in _statuses) ...[
                  const SizedBox(width: AppSpacing.sm),
                  _FilterChip(
                    label: s.statusLabel(status),
                    selected: controller.status == status,
                    onTap: () => controller.applyStatus(status),
                  ),
                ],
              ],
            ),
          ),
          Expanded(
            child: PaginatedListView<WithdrawRequest>(
              state: state,
              visibleItems: controller.visibleItems,
              isFiltering: controller.status != null,
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
              skeletonItemHeight: 76,
              itemBuilder: (context, request) => WithdrawRequestTile(
                request: request,
                onTap: () => Navigator.of(context).push(
                  MaterialPageRoute<void>(
                    builder: (_) => WithdrawDetailScreen(uuid: request.uuid),
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _FilterChip extends StatelessWidget {
  const _FilterChip({required this.label, required this.selected, required this.onTap});

  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return ChoiceChip(label: Text(label), selected: selected, onSelected: (_) => onTap());
  }
}

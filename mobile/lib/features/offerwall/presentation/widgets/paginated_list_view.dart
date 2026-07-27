import 'package:flutter/material.dart';

import '../../../../core/error/app_exception.dart';
import '../../../../core/theme/app_spacing.dart';
import '../../../../shared/extensions/context_extensions.dart';
import '../../../../shared/widgets/empty_view.dart';
import '../../../../shared/widgets/error_view.dart';
import '../../application/paginated_list_state.dart';
import 'list_skeleton.dart';

/// A reusable paginated list: skeleton → error/empty → list with infinite
/// scroll and a footer (load-more spinner / retry / bottom spacing), all with
/// pull-to-refresh. Shared across the offerwall and referral features.
class PaginatedListView<T> extends StatefulWidget {
  const PaginatedListView({
    required this.state,
    required this.visibleItems,
    required this.itemBuilder,
    required this.onRefresh,
    required this.onLoadMore,
    required this.onRetryLoadMore,
    required this.onRetry,
    required this.emptyTitle,
    required this.emptyBody,
    required this.emptyIcon,
    required this.noResultsTitle,
    required this.noResultsBody,
    required this.loadMoreErrorText,
    this.isFiltering = false,
    this.header,
    this.skeletonItemHeight = 84,
    super.key,
  });

  final PaginatedListState<T> state;
  final List<T> visibleItems;
  final Widget Function(BuildContext context, T item) itemBuilder;
  final Future<void> Function() onRefresh;
  final VoidCallback onLoadMore;
  final VoidCallback onRetryLoadMore;
  final VoidCallback onRetry;
  final String emptyTitle;
  final String emptyBody;
  final IconData emptyIcon;
  final String noResultsTitle;
  final String noResultsBody;
  final String loadMoreErrorText;
  final bool isFiltering;
  final Widget? header;
  final double skeletonItemHeight;

  @override
  State<PaginatedListView<T>> createState() => _PaginatedListViewState<T>();
}

class _PaginatedListViewState<T> extends State<PaginatedListView<T>> {
  final ScrollController _controller = ScrollController();

  @override
  void initState() {
    super.initState();
    _controller.addListener(_onScroll);
  }

  @override
  void dispose() {
    _controller
      ..removeListener(_onScroll)
      ..dispose();
    super.dispose();
  }

  void _onScroll() {
    if (!_controller.hasClients) {
      return;
    }
    final position = _controller.position;
    if (position.pixels >= position.maxScrollExtent - 320) {
      widget.onLoadMore();
    }
  }

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: widget.onRefresh,
      child: _buildChild(context),
    );
  }

  Widget _buildChild(BuildContext context) {
    final state = widget.state;

    if (state.isFirstLoad) {
      return ListSkeleton(itemHeight: widget.skeletonItemHeight);
    }

    if (state.status == ListStatus.error) {
      final error = state.error;
      return _Scrollable(
        child: ErrorView(
          message: error is AppException ? error.message : context.l10n.errorGeneric,
          onRetry: widget.onRetry,
        ),
      );
    }

    final items = widget.visibleItems;
    if (items.isEmpty) {
      return _Scrollable(
        child: EmptyView(
          icon: widget.isFiltering ? Icons.search_off : widget.emptyIcon,
          title: widget.isFiltering ? widget.noResultsTitle : widget.emptyTitle,
          message: widget.isFiltering ? widget.noResultsBody : widget.emptyBody,
        ),
      );
    }

    final headerCount = widget.header != null ? 1 : 0;
    return ListView.separated(
      controller: _controller,
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(AppSpacing.screen),
      itemCount: items.length + headerCount + 1, // + footer
      separatorBuilder: (context, index) => const SizedBox(height: AppSpacing.md),
      itemBuilder: (context, index) {
        if (headerCount == 1 && index == 0) {
          return widget.header!;
        }
        final itemIndex = index - headerCount;
        if (itemIndex >= items.length) {
          return _Footer(
            state: state,
            onRetry: widget.onRetryLoadMore,
            loadMoreErrorText: widget.loadMoreErrorText,
          );
        }
        return widget.itemBuilder(context, items[itemIndex]);
      },
    );
  }
}

class _Scrollable extends StatelessWidget {
  const _Scrollable({required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) => SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        child: ConstrainedBox(
          constraints: BoxConstraints(minHeight: constraints.maxHeight),
          child: child,
        ),
      ),
    );
  }
}

class _Footer extends StatelessWidget {
  const _Footer({required this.state, required this.onRetry, required this.loadMoreErrorText});

  final PaginatedListState<dynamic> state;
  final VoidCallback onRetry;
  final String loadMoreErrorText;

  @override
  Widget build(BuildContext context) {
    if (state.isLoadingMore) {
      return const Padding(
        padding: EdgeInsets.all(AppSpacing.lg),
        child: Center(child: CircularProgressIndicator()),
      );
    }
    if (state.loadMoreError != null) {
      return Padding(
        padding: const EdgeInsets.all(AppSpacing.lg),
        child: Center(
          child: TextButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh),
            label: Text(loadMoreErrorText),
          ),
        ),
      );
    }
    return const SizedBox(height: AppSpacing.xxl);
  }
}

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/error/app_exception.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../shared/extensions/context_extensions.dart';
import '../../../shared/widgets/app_scaffold.dart';
import '../../../shared/widgets/empty_view.dart';
import '../../../shared/widgets/error_view.dart';
import '../../../shared/widgets/loading_widget.dart';
import '../l10n/settings_strings.dart';
import '../models/faq.dart';
import '../providers/settings_providers.dart';

/// FAQ grouped by category, rendered as expandable tiles.
class FaqScreen extends ConsumerWidget {
  const FaqScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = SettingsStrings.of(context);
    final faqs = ref.watch(faqsProvider);

    return AppScaffold(
      appBar: AppBar(title: Text(s.faq)),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(faqsProvider),
        child: faqs.when(
          loading: () => const LoadingWidget(),
          error: (error, _) => ErrorView(
            message: error is AppException ? error.message : s.errorTitle,
            onRetry: () => ref.invalidate(faqsProvider),
          ),
          data: (categories) => categories.isEmpty
              ? _empty(s)
              : ListView(
                  padding: const EdgeInsets.all(AppSpacing.sm),
                  children: [
                    for (final category in categories) _CategorySection(category: category),
                  ],
                ),
        ),
      ),
    );
  }

  Widget _empty(SettingsStrings s) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        Padding(
          padding: const EdgeInsets.only(top: AppSpacing.xxxl),
          child: EmptyView(icon: Icons.help_outline, title: s.noFaqs, message: s.noFaqsBody),
        ),
      ],
    );
  }
}

class _CategorySection extends StatelessWidget {
  const _CategorySection({required this.category});

  final FaqCategory category;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(AppSpacing.md, AppSpacing.lg, AppSpacing.md, AppSpacing.xs),
          child: Text(
            category.category,
            style: context.textTheme.titleMedium?.copyWith(color: context.colors.primary),
          ),
        ),
        for (final item in category.items)
          Card(
            child: ExpansionTile(
              title: Text(item.question, style: context.textTheme.titleSmall),
              childrenPadding: const EdgeInsets.fromLTRB(
                AppSpacing.lg,
                0,
                AppSpacing.lg,
                AppSpacing.lg,
              ),
              children: [
                Align(
                  alignment: Alignment.centerLeft,
                  child: Text(item.answer, style: context.textTheme.bodyMedium),
                ),
              ],
            ),
          ),
      ],
    );
  }
}

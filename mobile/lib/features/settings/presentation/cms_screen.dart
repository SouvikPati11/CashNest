import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/error/app_exception.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../shared/extensions/context_extensions.dart';
import '../../../shared/widgets/app_scaffold.dart';
import '../../../shared/widgets/error_view.dart';
import '../../../shared/widgets/loading_widget.dart';
import '../l10n/settings_strings.dart';
import '../providers/settings_providers.dart';
import '../resources/settings_formatters.dart';

/// Renders a CMS content page (`about`, `privacy-policy`, `terms`).
class CmsScreen extends ConsumerWidget {
  const CmsScreen({required this.slug, super.key});

  final String slug;

  String _fallbackTitle(SettingsStrings s) {
    return switch (slug) {
      'about' => s.about,
      'privacy-policy' => s.privacyPolicy,
      'terms' => s.terms,
      _ => slug,
    };
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = SettingsStrings.of(context);
    final page = ref.watch(cmsPageProvider(slug));

    return AppScaffold(
      appBar: AppBar(title: Text(page.valueOrNull?.title ?? _fallbackTitle(s))),
      body: page.when(
        loading: () => const LoadingWidget(),
        error: (error, _) => ErrorView(
          message: error is AppException ? error.message : s.errorTitle,
          onRetry: () => ref.invalidate(cmsPageProvider(slug)),
        ),
        data: (data) => ListView(
          padding: const EdgeInsets.all(AppSpacing.screen),
          children: [
            Text(data.title, style: context.textTheme.headlineSmall),
            if (data.effectiveAt != null) ...[
              const SizedBox(height: AppSpacing.xs),
              Text(
                SettingsFormatters.date(data.effectiveAt!),
                style: context.textTheme.bodySmall?.copyWith(color: context.colors.onSurfaceVariant),
              ),
            ],
            const SizedBox(height: AppSpacing.lg),
            Text(data.body, style: context.textTheme.bodyLarge),
          ],
        ),
      ),
    );
  }
}

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../app/di/providers.dart';
import '../../../core/error/app_exception.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../shared/extensions/context_extensions.dart';
import '../../../shared/widgets/app_scaffold.dart';
import '../../../shared/widgets/error_view.dart';
import '../../../shared/widgets/loading_widget.dart';
import '../l10n/settings_strings.dart';
import '../models/app_version_info.dart';
import '../providers/settings_providers.dart';

/// App version info: the installed build (foundation [AppInfoService]) and the
/// latest available version + update gate (`GET /v1/app/version`).
class AppVersionScreen extends ConsumerWidget {
  const AppVersionScreen({super.key});

  Future<void> _copyStoreLink(BuildContext context, String url) async {
    final s = SettingsStrings.of(context);
    await Clipboard.setData(ClipboardData(text: url));
    if (context.mounted) {
      ScaffoldMessenger.of(context)
        ..hideCurrentSnackBar()
        ..showSnackBar(SnackBar(content: Text(s.linkCopied)));
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = SettingsStrings.of(context);
    final remote = ref.watch(appVersionProvider);
    final installed = ref.watch(appInfoServiceProvider).current;

    return AppScaffold(
      appBar: AppBar(title: Text(s.appVersion)),
      body: remote.when(
        loading: () => const LoadingWidget(),
        error: (error, _) => ErrorView(
          message: error is AppException ? error.message : s.errorTitle,
          onRetry: () => ref.invalidate(appVersionProvider),
        ),
        data: (info) => ListView(
          padding: const EdgeInsets.all(AppSpacing.screen),
          children: [
            Center(
              child: Column(
                children: [
                  Icon(Icons.savings_outlined, size: 56, color: context.colors.primary),
                  const SizedBox(height: AppSpacing.sm),
                  Text(
                    installed?.appName ?? 'CashNest',
                    style: context.textTheme.titleLarge,
                  ),
                  Text(
                    '${s.installedVersion}: ${installed?.version ?? '—'} (${installed?.buildNumber ?? '—'})',
                    style: context.textTheme.bodyMedium?.copyWith(color: context.colors.onSurfaceVariant),
                  ),
                ],
              ),
            ),
            const SizedBox(height: AppSpacing.xl),
            _StatusBanner(info: info),
            const SizedBox(height: AppSpacing.lg),
            Card(
              child: Padding(
                padding: const EdgeInsets.all(AppSpacing.lg),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(s.latestVersion, style: context.textTheme.titleSmall),
                        Text(info.latestVersion, style: context.textTheme.titleSmall),
                      ],
                    ),
                    if (info.changelog != null && info.changelog!.isNotEmpty) ...[
                      const SizedBox(height: AppSpacing.md),
                      Text(s.changelog, style: context.textTheme.labelLarge),
                      const SizedBox(height: AppSpacing.xs),
                      Text(info.changelog!, style: context.textTheme.bodyMedium),
                    ],
                  ],
                ),
              ),
            ),
            if (info.storeUrl != null && info.storeUrl!.isNotEmpty) ...[
              const SizedBox(height: AppSpacing.lg),
              FilledButton.icon(
                onPressed: () => _copyStoreLink(context, info.storeUrl!),
                icon: const Icon(Icons.copy),
                label: Text(s.copyStoreLink),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _StatusBanner extends StatelessWidget {
  const _StatusBanner({required this.info});

  final AppVersionInfo info;

  @override
  Widget build(BuildContext context) {
    final s = SettingsStrings.of(context);
    final scheme = context.colors;

    final (Color bg, Color fg, IconData icon, String text) = info.forceUpdate
        ? (scheme.errorContainer, scheme.onErrorContainer, Icons.warning_amber, s.forceUpdate)
        : info.updateAvailable
            ? (scheme.tertiaryContainer, scheme.onTertiaryContainer, Icons.system_update, s.updateAvailable)
            : (scheme.secondaryContainer, scheme.onSecondaryContainer, Icons.check_circle_outline, s.upToDate);

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(AppSpacing.md),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(12)),
      child: Row(
        children: [
          Icon(icon, color: fg),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Text(text, style: context.textTheme.bodyMedium?.copyWith(color: fg)),
          ),
        ],
      ),
    );
  }
}

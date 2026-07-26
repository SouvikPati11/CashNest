import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../app/di/providers.dart';
import '../../core/responsive/responsive.dart';
import '../../core/theme/app_spacing.dart';
import '../../shared/extensions/context_extensions.dart';
import '../../shared/widgets/app_scaffold.dart';

/// Foundation landing screen.
///
/// Not a business feature — it verifies the foundation is wired: it renders
/// through [AppScaffold], reads localized strings, and drives runtime theme and
/// language switching. Real features replace this route.
class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = context.l10n;
    final themeMode = ref.watch(themeModeControllerProvider);
    final locale = ref.watch(localeControllerProvider);

    return AppScaffold(
      appBar: AppBar(title: Text(l10n.appName)),
      padding: const EdgeInsets.all(AppSpacing.screen),
      body: ResponsiveLayout(
        phone: (context) => _Body(themeMode: themeMode, localeCode: locale.languageCode),
        tablet: (context) => Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 560),
            child: _Body(themeMode: themeMode, localeCode: locale.languageCode),
          ),
        ),
      ),
    );
  }
}

class _Body extends StatelessWidget {
  const _Body({required this.themeMode, required this.localeCode});

  final ThemeMode themeMode;
  final String localeCode;

  @override
  Widget build(BuildContext context) {
    final l10n = context.l10n;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Icon(Icons.savings_outlined, size: 56, color: context.colors.primary),
        const SizedBox(height: AppSpacing.md),
        Text(l10n.foundationReady, style: context.textTheme.titleLarge, textAlign: TextAlign.center),
        const SizedBox(height: AppSpacing.xl),
        Card(
          child: Padding(
            padding: const EdgeInsets.all(AppSpacing.lg),
            child: Column(
              children: [
                _ThemeToggle(current: themeMode),
                const Divider(height: AppSpacing.xl),
                _LocaleToggle(current: localeCode),
              ],
            ),
          ),
        ),
      ],
    );
  }
}

class _ThemeToggle extends ConsumerWidget {
  const _ThemeToggle({required this.current});

  final ThemeMode current;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Row(
      children: [
        const Icon(Icons.brightness_6_outlined),
        const SizedBox(width: AppSpacing.md),
        const Expanded(child: Text('Theme')),
        SegmentedButton<ThemeMode>(
          segments: const [
            ButtonSegment(value: ThemeMode.system, icon: Icon(Icons.settings_suggest_outlined)),
            ButtonSegment(value: ThemeMode.light, icon: Icon(Icons.light_mode_outlined)),
            ButtonSegment(value: ThemeMode.dark, icon: Icon(Icons.dark_mode_outlined)),
          ],
          selected: {current},
          showSelectedIcon: false,
          onSelectionChanged: (selection) =>
              ref.read(themeModeControllerProvider.notifier).set(selection.first),
        ),
      ],
    );
  }
}

class _LocaleToggle extends ConsumerWidget {
  const _LocaleToggle({required this.current});

  final String current;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Row(
      children: [
        const Icon(Icons.translate_outlined),
        const SizedBox(width: AppSpacing.md),
        const Expanded(child: Text('Language')),
        SegmentedButton<String>(
          segments: const [
            ButtonSegment(value: 'en', label: Text('EN')),
            ButtonSegment(value: 'bn', label: Text('বাং')),
          ],
          selected: {current},
          showSelectedIcon: false,
          onSelectionChanged: (selection) =>
              ref.read(localeControllerProvider.notifier).set(Locale(selection.first)),
        ),
      ],
    );
  }
}

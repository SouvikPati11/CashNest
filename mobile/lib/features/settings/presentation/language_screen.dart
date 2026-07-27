import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../app/di/providers.dart';
import '../../../shared/extensions/context_extensions.dart';
import '../../../shared/widgets/app_scaffold.dart';
import '../l10n/settings_strings.dart';
import '../providers/settings_providers.dart';

/// Language selection. Switches the app locale at runtime (foundation
/// [localeControllerProvider]) and persists the choice to the backend.
class LanguageScreen extends ConsumerWidget {
  const LanguageScreen({super.key});

  static const List<({String code, String label})> _languages = [
    (code: 'en', label: 'English'),
    (code: 'bn', label: 'বাংলা'),
  ];

  Future<void> _select(WidgetRef ref, String code) async {
    ref.read(localeControllerProvider.notifier).set(Locale(code));
    // Best-effort backend sync; the runtime switch above already took effect.
    await ref.read(settingsRepositoryProvider).updatePreferences(language: code);
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = SettingsStrings.of(context);
    final current = ref.watch(localeControllerProvider).languageCode;

    return AppScaffold(
      appBar: AppBar(title: Text(s.language)),
      body: ListView(
        children: [
          for (final lang in _languages)
            ListTile(
              title: Text(lang.label),
              trailing: current == lang.code
                  ? Icon(Icons.check, color: context.colors.primary)
                  : null,
              onTap: () => _select(ref, lang.code),
            ),
        ],
      ),
    );
  }
}

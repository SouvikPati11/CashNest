import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../app/di/providers.dart';
import '../../../shared/extensions/context_extensions.dart';
import '../../../shared/widgets/app_scaffold.dart';
import '../l10n/settings_strings.dart';
import '../providers/settings_providers.dart';

/// Theme-mode selection. Switches the theme at runtime (foundation
/// [themeModeControllerProvider]) and persists the choice to the backend.
class ThemeScreen extends ConsumerWidget {
  const ThemeScreen({super.key});

  static String _apiValue(ThemeMode mode) => switch (mode) {
        ThemeMode.light => 'light',
        ThemeMode.dark => 'dark',
        ThemeMode.system => 'system',
      };

  Future<void> _select(WidgetRef ref, ThemeMode mode) async {
    ref.read(themeModeControllerProvider.notifier).set(mode);
    await ref.read(settingsRepositoryProvider).updatePreferences(themeMode: _apiValue(mode));
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = SettingsStrings.of(context);
    final current = ref.watch(themeModeControllerProvider);

    final options = <({ThemeMode mode, String label})>[
      (mode: ThemeMode.system, label: s.systemTheme),
      (mode: ThemeMode.light, label: s.lightTheme),
      (mode: ThemeMode.dark, label: s.darkTheme),
    ];

    return AppScaffold(
      appBar: AppBar(title: Text(s.theme)),
      body: ListView(
        children: [
          for (final option in options)
            ListTile(
              title: Text(option.label),
              trailing: current == option.mode
                  ? Icon(Icons.check, color: context.colors.primary)
                  : null,
              onTap: () => _select(ref, option.mode),
            ),
        ],
      ),
    );
  }
}

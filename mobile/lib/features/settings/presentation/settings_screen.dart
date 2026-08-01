import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../app/di/providers.dart';
import '../../../shared/extensions/context_extensions.dart';
import '../../../shared/widgets/app_scaffold.dart';
import '../../auth/providers/auth_providers.dart';
import '../../notification/presentation/notification_preferences_screen.dart';
import '../l10n/settings_strings.dart';
import 'app_version_screen.dart';
import 'cms_screen.dart';
import 'faq_screen.dart';
import 'language_screen.dart';
import 'profile_screen.dart';
import 'theme_screen.dart';
import 'widgets/settings_tile.dart';

/// Settings hub linking to every settings sub-screen.
class SettingsScreen extends ConsumerWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = SettingsStrings.of(context);
    final themeMode = ref.watch(themeModeControllerProvider);
    final locale = ref.watch(localeControllerProvider);

    void push(Widget screen) {
      Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => screen));
    }

    return AppScaffold(
      appBar: AppBar(title: Text(s.title)),
      body: ListView(
        children: [
          SettingsSectionHeader(title: s.accountSection),
          SettingsTile(
            icon: Icons.person_outline,
            title: s.profile,
            onTap: () => push(const ProfileScreen()),
          ),
          SettingsTile(
            icon: Icons.notifications_outlined,
            title: s.notificationSettings,
            onTap: () => push(const NotificationPreferencesScreen()),
          ),

          SettingsSectionHeader(title: s.preferencesSection),
          SettingsTile(
            icon: Icons.language_outlined,
            title: s.language,
            value: _languageLabel(locale.languageCode),
            onTap: () => push(const LanguageScreen()),
          ),
          SettingsTile(
            icon: Icons.brightness_6_outlined,
            title: s.theme,
            value: _themeLabel(s, themeMode),
            onTap: () => push(const ThemeScreen()),
          ),

          SettingsSectionHeader(title: s.aboutSection),
          SettingsTile(
            icon: Icons.help_outline,
            title: s.faq,
            onTap: () => push(const FaqScreen()),
          ),
          // Support tickets are hidden until the backend /support/* API exists
          // (only the DB tables are present today). Screens/models retained.
          SettingsTile(
            icon: Icons.info_outline,
            title: s.about,
            onTap: () => push(const CmsScreen(slug: 'about')),
          ),
          SettingsTile(
            icon: Icons.privacy_tip_outlined,
            title: s.privacyPolicy,
            onTap: () => push(const CmsScreen(slug: 'privacy-policy')),
          ),
          SettingsTile(
            icon: Icons.description_outlined,
            title: s.terms,
            onTap: () => push(const CmsScreen(slug: 'terms')),
          ),
          SettingsTile(
            icon: Icons.system_update_outlined,
            title: s.appVersion,
            onTap: () => push(const AppVersionScreen()),
          ),

          const Divider(height: 24),
          SettingsTile(
            icon: Icons.logout,
            title: s.logout,
            destructive: true,
            trailing: const SizedBox.shrink(),
            onTap: () => _confirmLogout(context, ref),
          ),
          const SizedBox(height: 24),
        ],
      ),
    );
  }

  String _languageLabel(String code) => code == 'bn' ? 'বাংলা' : 'English';

  String _themeLabel(SettingsStrings s, ThemeMode mode) {
    return switch (mode) {
      ThemeMode.light => s.lightTheme,
      ThemeMode.dark => s.darkTheme,
      ThemeMode.system => s.systemTheme,
    };
  }

  Future<void> _confirmLogout(BuildContext context, WidgetRef ref) async {
    final s = SettingsStrings.of(context);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: Text(s.logoutConfirmTitle),
        content: Text(s.logoutConfirmBody),
        actions: [
          TextButton(onPressed: () => Navigator.of(dialogContext).pop(false), child: Text(s.cancel)),
          FilledButton(
            style: FilledButton.styleFrom(backgroundColor: context.colors.error),
            onPressed: () => Navigator.of(dialogContext).pop(true),
            child: Text(s.logout),
          ),
        ],
      ),
    );
    if (confirmed == true) {
      await ref.read(authControllerProvider.notifier).logout();
    }
  }
}

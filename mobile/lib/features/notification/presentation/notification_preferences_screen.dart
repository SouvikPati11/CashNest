import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/error/app_exception.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../shared/widgets/app_scaffold.dart';
import '../../../shared/widgets/error_view.dart';
import '../../../shared/widgets/loading_widget.dart';
import '../application/preferences_controller.dart';
import '../l10n/notification_strings.dart';
import '../models/notification_preferences.dart';
import '../providers/notification_providers.dart';

/// Notification preferences: push, transactional, and promotional toggles.
class NotificationPreferencesScreen extends ConsumerStatefulWidget {
  const NotificationPreferencesScreen({super.key});

  @override
  ConsumerState<NotificationPreferencesScreen> createState() =>
      _NotificationPreferencesScreenState();
}

class _NotificationPreferencesScreenState extends ConsumerState<NotificationPreferencesScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(preferencesControllerProvider.notifier).load();
    });
  }

  @override
  Widget build(BuildContext context) {
    final s = NotificationStrings.of(context);
    final state = ref.watch(preferencesControllerProvider);
    final controller = ref.read(preferencesControllerProvider.notifier);

    return AppScaffold(
      appBar: AppBar(title: Text(s.preferences)),
      body: switch (state) {
        AsyncData(:final value) => _Body(prefs: value, controller: controller),
        AsyncError(:final error) => ErrorView(
            message: error is AppException ? error.message : s.errorTitle,
            onRetry: () => ref.read(preferencesControllerProvider.notifier).load(),
          ),
        _ => const LoadingWidget(),
      },
    );
  }
}

class _Body extends StatelessWidget {
  const _Body({required this.prefs, required this.controller});

  final NotificationPreferences prefs;
  final PreferencesController controller;

  @override
  Widget build(BuildContext context) {
    final s = NotificationStrings.of(context);
    return ListView(
      padding: const EdgeInsets.all(AppSpacing.screen),
      children: [
        SwitchListTile(
          title: Text(s.pushEnabled),
          subtitle: Text(s.pushEnabledDesc),
          value: prefs.pushEnabled,
          onChanged: (v) => controller.setPushEnabled(v),
        ),
        SwitchListTile(
          title: Text(s.transactional),
          subtitle: Text(s.transactionalDesc),
          value: prefs.transactional,
          onChanged: prefs.pushEnabled ? (v) => controller.setTransactional(v) : null,
        ),
        SwitchListTile(
          title: Text(s.promotional),
          subtitle: Text(s.promotionalDesc),
          value: prefs.promotional,
          onChanged: prefs.pushEnabled ? (v) => controller.setPromotional(v) : null,
        ),
      ],
    );
  }
}

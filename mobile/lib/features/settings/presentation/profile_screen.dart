import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/error/app_exception.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../shared/extensions/context_extensions.dart';
import '../../../shared/widgets/app_scaffold.dart';
import '../../../shared/widgets/error_view.dart';
import '../../../shared/widgets/loading_widget.dart';
import '../l10n/settings_strings.dart';
import '../models/user_profile.dart';
import '../providers/settings_providers.dart';
import '../resources/settings_formatters.dart';
import 'edit_profile_screen.dart';
import 'widgets/settings_tile.dart';

/// Read-only profile view with an entry point to edit.
class ProfileScreen extends ConsumerStatefulWidget {
  const ProfileScreen({super.key});

  @override
  ConsumerState<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends ConsumerState<ProfileScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(profileControllerProvider.notifier).load();
    });
  }

  @override
  Widget build(BuildContext context) {
    final s = SettingsStrings.of(context);
    final state = ref.watch(profileControllerProvider);

    return AppScaffold(
      appBar: AppBar(
        title: Text(s.profile),
        actions: [
          if (state.hasValue)
            IconButton(
              tooltip: s.editProfile,
              icon: const Icon(Icons.edit_outlined),
              onPressed: () => Navigator.of(context).push(
                MaterialPageRoute<void>(
                  builder: (_) => EditProfileScreen(profile: state.value!),
                ),
              ),
            ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.read(profileControllerProvider.notifier).refresh(),
        child: switch (state) {
          AsyncData(:final value) => _Body(profile: value),
          AsyncError(:final error) => _ErrorState(
              error: error,
              onRetry: () => ref.read(profileControllerProvider.notifier).load(),
            ),
          _ => const LoadingWidget(),
        },
      ),
    );
  }
}

class _Body extends StatelessWidget {
  const _Body({required this.profile});

  final UserProfile profile;

  @override
  Widget build(BuildContext context) {
    final s = SettingsStrings.of(context);

    return ListView(
      padding: const EdgeInsets.symmetric(vertical: AppSpacing.lg),
      children: [
        Center(
          child: Column(
            children: [
              CircleAvatar(
                radius: 44,
                backgroundColor: context.colors.primaryContainer,
                foregroundImage: (profile.avatarUrl != null && profile.avatarUrl!.isNotEmpty)
                    ? NetworkImage(profile.avatarUrl!)
                    : null,
                child: Text(
                  profile.name.isNotEmpty ? profile.name.characters.first.toUpperCase() : '?',
                  style: context.textTheme.headlineMedium
                      ?.copyWith(color: context.colors.onPrimaryContainer),
                ),
              ),
              const SizedBox(height: AppSpacing.md),
              Text(profile.name, style: context.textTheme.titleLarge),
              Text(
                profile.email,
                style: context.textTheme.bodyMedium?.copyWith(color: context.colors.onSurfaceVariant),
              ),
            ],
          ),
        ),
        const SizedBox(height: AppSpacing.lg),
        if (profile.referralCode != null)
          SettingsTile(icon: Icons.card_giftcard_outlined, title: s.referralCode, value: profile.referralCode),
        if (profile.countryCode != null)
          SettingsTile(icon: Icons.public, title: s.country, value: profile.countryCode),
        if (profile.kycStatus != null)
          SettingsTile(icon: Icons.verified_user_outlined, title: s.kycStatus, value: profile.kycStatus),
        if (profile.createdAt != null)
          SettingsTile(
            icon: Icons.calendar_today_outlined,
            title: s.memberSince,
            value: SettingsFormatters.date(profile.createdAt!),
          ),
      ],
    );
  }
}

class _ErrorState extends StatelessWidget {
  const _ErrorState({required this.error, required this.onRetry});

  final Object error;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    final s = SettingsStrings.of(context);
    final err = error;
    return LayoutBuilder(
      builder: (context, constraints) => SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        child: ConstrainedBox(
          constraints: BoxConstraints(minHeight: constraints.maxHeight),
          child: ErrorView(
            message: err is AppException ? err.message : s.errorTitle,
            onRetry: onRetry,
          ),
        ),
      ),
    );
  }
}

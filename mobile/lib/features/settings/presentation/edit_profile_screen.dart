import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/error/app_exception.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../shared/extensions/context_extensions.dart';
import '../../../shared/widgets/app_scaffold.dart';
import '../l10n/settings_strings.dart';
import '../models/user_profile.dart';
import '../providers/settings_providers.dart';

/// Edit the editable profile fields (name, country) via `PUT /v1/profile`.
class EditProfileScreen extends ConsumerStatefulWidget {
  const EditProfileScreen({required this.profile, super.key});

  final UserProfile profile;

  @override
  ConsumerState<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends ConsumerState<EditProfileScreen> {
  late final TextEditingController _name = TextEditingController(text: widget.profile.name);
  late final TextEditingController _country =
      TextEditingController(text: widget.profile.countryCode ?? '');
  late final TextEditingController _email = TextEditingController(text: widget.profile.email);

  @override
  void dispose() {
    _name.dispose();
    _country.dispose();
    _email.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    final s = SettingsStrings.of(context);
    final controller = ref.read(editProfileControllerProvider(widget.profile).notifier);
    try {
      final updated = await controller.save();
      if (!mounted) {
        return;
      }
      ref.read(profileControllerProvider.notifier).setProfile(updated);
      Navigator.of(context).pop();
      ScaffoldMessenger.of(context)
        ..hideCurrentSnackBar()
        ..showSnackBar(SnackBar(content: Text(s.saved)));
    } on AppException {
      // Error is surfaced via the controller state below.
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = SettingsStrings.of(context);
    final state = ref.watch(editProfileControllerProvider(widget.profile));
    final controller = ref.read(editProfileControllerProvider(widget.profile).notifier);

    return AppScaffold(
      appBar: AppBar(title: Text(s.editProfile)),
      body: ListView(
        padding: const EdgeInsets.all(AppSpacing.screen),
        children: [
          TextField(
            controller: _name,
            decoration: InputDecoration(
              labelText: s.name,
              errorText: (state.name.trim().isNotEmpty && state.name.trim().length < 2)
                  ? s.nameTooShort
                  : null,
            ),
            textCapitalization: TextCapitalization.words,
            onChanged: controller.setName,
          ),
          const SizedBox(height: AppSpacing.lg),
          TextField(
            controller: _country,
            decoration: InputDecoration(labelText: s.country, hintText: s.countryHint),
            textCapitalization: TextCapitalization.characters,
            onChanged: controller.setCountryCode,
          ),
          const SizedBox(height: AppSpacing.lg),
          TextField(
            enabled: false,
            controller: _email,
            decoration: InputDecoration(labelText: s.email),
          ),
          if (state.error != null) ...[
            const SizedBox(height: AppSpacing.md),
            Text(
              state.error!.message,
              style: context.textTheme.bodyMedium?.copyWith(color: context.colors.error),
            ),
          ],
          const SizedBox(height: AppSpacing.xl),
          FilledButton(
            onPressed: state.canSave ? _save : null,
            child: state.saving
                ? Text(s.saving)
                : Text(s.save),
          ),
        ],
      ),
    );
  }
}

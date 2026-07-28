import 'package:flutter/material.dart';

import '../../../../core/theme/app_gradients.dart';
import '../../../../core/theme/app_spacing.dart';
import '../../../../shared/extensions/context_extensions.dart';
import '../../../auth/models/auth_user.dart';
import '../../l10n/home_strings.dart';

/// Greeting header showing the signed-in user's avatar (with a brand gradient
/// ring) and name.
class ProfileHeader extends StatelessWidget {
  const ProfileHeader({required this.user, super.key});

  final AuthUser? user;

  @override
  Widget build(BuildContext context) {
    final s = HomeStrings.of(context);
    final name = (user?.name.trim().isNotEmpty ?? false) ? user!.name.trim() : s.guest;

    return Row(
      children: [
        Container(
          padding: const EdgeInsets.all(2.5),
          decoration: const BoxDecoration(
            shape: BoxShape.circle,
            gradient: AppGradients.brand,
          ),
          child: CircleAvatar(
            radius: 25,
            backgroundColor: context.colors.surfaceContainerHighest,
            foregroundImage: _avatarImage(),
            child: Text(
              _initials(name),
              style: context.textTheme.titleMedium?.copyWith(
                color: context.colors.onSurface,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ),
        const SizedBox(width: AppSpacing.md),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                '${s.greeting} 👋',
                style: context.textTheme.bodyMedium?.copyWith(
                  color: context.colors.onSurfaceVariant,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                name,
                style: context.textTheme.titleLarge,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ],
          ),
        ),
      ],
    );
  }

  ImageProvider<Object>? _avatarImage() {
    final url = user?.avatarUrl;
    return (url != null && url.isNotEmpty) ? NetworkImage(url) : null;
  }

  String _initials(String name) {
    final parts = name.split(RegExp(r'\s+')).where((p) => p.isNotEmpty).toList();
    if (parts.isEmpty) {
      return '?';
    }
    if (parts.length == 1) {
      return parts.first.characters.first.toUpperCase();
    }
    return (parts.first.characters.first + parts.last.characters.first).toUpperCase();
  }
}

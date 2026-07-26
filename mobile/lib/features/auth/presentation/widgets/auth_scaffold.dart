import 'package:flutter/material.dart';

import '../../../../core/theme/app_spacing.dart';
import '../../../../shared/extensions/context_extensions.dart';
import '../../../../shared/widgets/offline_banner.dart';

/// Shared responsive scaffold for auth screens.
///
/// Centers a max-width form column, scrolls to avoid keyboard overflow, and
/// surfaces the foundation [OfflineBanner]. Dark mode is inherited from the
/// active theme.
class AuthScaffold extends StatelessWidget {
  const AuthScaffold({
    required this.title,
    required this.subtitle,
    required this.children,
    this.showBack = false,
    super.key,
  });

  final String title;
  final String subtitle;
  final List<Widget> children;
  final bool showBack;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: showBack ? AppBar(automaticallyImplyLeading: true) : null,
      body: Column(
        children: [
          const OfflineBanner(),
          Expanded(
            child: SafeArea(
              child: Center(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.all(AppSpacing.xl),
                  child: ConstrainedBox(
                    constraints: const BoxConstraints(maxWidth: 440),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Icon(Icons.savings_outlined, size: 56, color: context.colors.primary),
                        const SizedBox(height: AppSpacing.lg),
                        Text(title, style: context.textTheme.headlineMedium),
                        const SizedBox(height: AppSpacing.xs),
                        Text(
                          subtitle,
                          style: context.textTheme.bodyMedium
                              ?.copyWith(color: context.colors.onSurfaceVariant),
                        ),
                        const SizedBox(height: AppSpacing.xl),
                        ...children,
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

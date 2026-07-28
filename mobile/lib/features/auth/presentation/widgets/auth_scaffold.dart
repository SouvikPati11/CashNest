import 'package:flutter/material.dart';

import '../../../../core/theme/app_gradients.dart';
import '../../../../core/theme/app_radius.dart';
import '../../../../core/theme/app_spacing.dart';
import '../../../../shared/extensions/context_extensions.dart';
import '../../../../shared/widgets/fade_slide_in.dart';
import '../../../../shared/widgets/offline_banner.dart';

/// Shared responsive scaffold for auth screens.
///
/// Centers a max-width form column on a premium branded header (gradient logo
/// badge with a soft glow), scrolls to avoid keyboard overflow, and surfaces the
/// foundation [OfflineBanner]. Dark mode is inherited from the active theme.
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
      appBar: showBack
          ? AppBar(automaticallyImplyLeading: true, backgroundColor: Colors.transparent)
          : null,
      body: Column(
        children: [
          const OfflineBanner(),
          Expanded(
            child: SafeArea(
              child: Center(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.symmetric(
                    horizontal: AppSpacing.xl,
                    vertical: AppSpacing.xxl,
                  ),
                  child: ConstrainedBox(
                    constraints: const BoxConstraints(maxWidth: 440),
                    child: FadeSlideIn(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          Center(child: _LogoBadge()),
                          const SizedBox(height: AppSpacing.xl),
                          Text(
                            title,
                            textAlign: TextAlign.center,
                            style: context.textTheme.headlineSmall,
                          ),
                          const SizedBox(height: AppSpacing.sm),
                          Text(
                            subtitle,
                            textAlign: TextAlign.center,
                            style: context.textTheme.bodyMedium
                                ?.copyWith(color: context.colors.onSurfaceVariant),
                          ),
                          const SizedBox(height: AppSpacing.xxl),
                          ...children,
                        ],
                      ),
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

class _LogoBadge extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
      width: 76,
      height: 76,
      decoration: BoxDecoration(
        gradient: AppGradients.brand,
        borderRadius: AppRadius.xlAll,
        boxShadow: AppGradients.glow(context.colors.primary, opacity: 0.5),
      ),
      child: const Icon(Icons.savings_rounded, size: 38, color: Colors.white),
    );
  }
}

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/localization/app_localizations.dart';
import '../../core/router/app_startup_provider.dart';
import '../../core/theme/app_spacing.dart';

/// Splash route shown while the app finishes warming up.
///
/// Foundation-level only: it displays branding, then flips the startup flag so
/// the router redirects onward. No business logic lives here.
class SplashScreen extends ConsumerStatefulWidget {
  const SplashScreen({super.key});

  @override
  ConsumerState<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends ConsumerState<SplashScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _completeStartup());
  }

  Future<void> _completeStartup() async {
    // Minimum splash window for a smooth first impression.
    await Future<void>.delayed(const Duration(milliseconds: 600));
    if (mounted) {
      ref.read(appStartupProvider.notifier).state = true;
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);

    return Scaffold(
      backgroundColor: theme.colorScheme.primary,
      body: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.savings_outlined, size: 72, color: theme.colorScheme.onPrimary),
            const SizedBox(height: AppSpacing.lg),
            Text(
              l10n.appName,
              style: theme.textTheme.headlineMedium?.copyWith(
                color: theme.colorScheme.onPrimary,
                fontWeight: FontWeight.w700,
              ),
            ),
            const SizedBox(height: AppSpacing.xl),
            SizedBox(
              width: 28,
              height: 28,
              child: CircularProgressIndicator(
                strokeWidth: 2.5,
                color: theme.colorScheme.onPrimary,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

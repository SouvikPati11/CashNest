import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../core/localization/app_localizations.dart';
import '../../core/router/app_routes.dart';
import '../../core/theme/app_spacing.dart';

/// Full-screen error route used by GoRouter's `errorBuilder` and the `/error`
/// path (e.g. unmatched routes or navigation failures).
class ErrorScreen extends StatelessWidget {
  const ErrorScreen({this.message, super.key});

  final String? message;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);

    return Scaffold(
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(AppSpacing.xl),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(Icons.error_outline, size: 64, color: theme.colorScheme.error),
              const SizedBox(height: AppSpacing.lg),
              Text(
                message ?? l10n.errorGeneric,
                textAlign: TextAlign.center,
                style: theme.textTheme.bodyLarge,
              ),
              const SizedBox(height: AppSpacing.xl),
              FilledButton(
                onPressed: () => context.go(AppRoutes.home),
                child: Text(l10n.home),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

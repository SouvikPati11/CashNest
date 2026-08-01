import 'package:flutter/material.dart';

import '../../core/theme/app_spacing.dart';
import '../extensions/context_extensions.dart';
import 'app_button.dart';
import 'ds/icon_badge.dart';

/// Inline error state with an optional retry action, for use inside screens.
class ErrorView extends StatelessWidget {
  const ErrorView({
    required this.message,
    this.onRetry,
    this.icon = Icons.error_outline_rounded,
    super.key,
  });

  final String message;
  final VoidCallback? onRetry;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.xl),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            IconBadge(icon: icon, size: 72, iconSize: 34, color: context.colors.error),
            const SizedBox(height: AppSpacing.lg),
            Text(
              message,
              textAlign: TextAlign.center,
              style: context.textTheme.bodyLarge,
            ),
            if (onRetry != null) ...[
              const SizedBox(height: AppSpacing.lg),
              AppButton(
                label: context.l10n.retry,
                onPressed: onRetry,
                icon: Icons.refresh,
                variant: AppButtonVariant.secondary,
                expanded: false,
              ),
            ],
          ],
        ),
      ),
    );
  }
}

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../app/di/providers.dart';
import '../../core/theme/app_spacing.dart';
import '../extensions/context_extensions.dart';

/// A slim banner shown above content whenever the device goes offline.
///
/// Driven by [connectivityStatusProvider]; renders nothing while online.
class OfflineBanner extends ConsumerWidget {
  const OfflineBanner({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final status = ref.watch(connectivityStatusProvider);
    final isOnline = status.valueOrNull ?? true;

    if (isOnline) {
      return const SizedBox.shrink();
    }

    return Material(
      color: context.colors.errorContainer,
      child: SafeArea(
        bottom: false,
        child: Padding(
          padding: const EdgeInsets.symmetric(
            horizontal: AppSpacing.lg,
            vertical: AppSpacing.sm,
          ),
          child: Row(
            children: [
              Icon(Icons.wifi_off, size: 18, color: context.colors.onErrorContainer),
              const SizedBox(width: AppSpacing.sm),
              Expanded(
                child: Text(
                  context.l10n.offlineMessage,
                  style: context.textTheme.bodySmall?.copyWith(
                    color: context.colors.onErrorContainer,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

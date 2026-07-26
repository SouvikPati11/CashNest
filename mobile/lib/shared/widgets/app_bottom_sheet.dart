import 'package:flutter/material.dart';

import '../../core/theme/app_spacing.dart';

/// Helpers for consistent modal bottom sheets.
abstract final class AppBottomSheet {
  const AppBottomSheet._();

  /// Show a scrollable, rounded modal bottom sheet returning an optional result.
  static Future<T?> show<T>(
    BuildContext context, {
    required WidgetBuilder builder,
    bool isScrollControlled = true,
    bool useSafeArea = true,
  }) {
    return showModalBottomSheet<T>(
      context: context,
      isScrollControlled: isScrollControlled,
      useSafeArea: useSafeArea,
      builder: (context) => Padding(
        padding: EdgeInsets.only(
          left: AppSpacing.lg,
          right: AppSpacing.lg,
          top: AppSpacing.sm,
          bottom: MediaQuery.viewInsetsOf(context).bottom + AppSpacing.lg,
        ),
        child: builder(context),
      ),
    );
  }
}

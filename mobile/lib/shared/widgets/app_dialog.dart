import 'package:flutter/material.dart';

import '../extensions/context_extensions.dart';

/// Helpers for consistent Material 3 dialogs.
abstract final class AppDialog {
  const AppDialog._();

  /// Confirmation dialog resolving to `true` (confirm) or `false`/`null`.
  static Future<bool?> confirm(
    BuildContext context, {
    required String title,
    required String message,
    String? confirmLabel,
    String? cancelLabel,
    bool destructive = false,
  }) {
    final l10n = context.l10n;
    return showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(title),
        content: Text(message),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(false),
            child: Text(cancelLabel ?? l10n.cancel),
          ),
          FilledButton(
            onPressed: () => Navigator.of(context).pop(true),
            style: destructive
                ? FilledButton.styleFrom(backgroundColor: context.colors.error)
                : null,
            child: Text(confirmLabel ?? l10n.ok),
          ),
        ],
      ),
    );
  }

  /// Simple message dialog with a single dismiss action.
  static Future<void> message(
    BuildContext context, {
    required String title,
    required String message,
  }) {
    final l10n = context.l10n;
    return showDialog<void>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(title),
        content: Text(message),
        actions: [
          FilledButton(
            onPressed: () => Navigator.of(context).pop(),
            child: Text(l10n.ok),
          ),
        ],
      ),
    );
  }
}

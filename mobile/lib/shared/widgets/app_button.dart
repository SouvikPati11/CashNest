import 'package:flutter/material.dart';

import '../../core/theme/app_gradients.dart';
import '../../core/theme/app_radius.dart';
import '../extensions/context_extensions.dart';

/// Visual variants for [AppButton].
enum AppButtonVariant { primary, secondary, text }

/// Unified button component with primary/secondary/text variants and a built-in
/// loading state that disables interaction and shows a spinner.
///
/// The primary variant renders on the brand gradient with a soft glow for a
/// premium CTA; secondary and text defer to the themed outlined/text buttons.
class AppButton extends StatelessWidget {
  const AppButton({
    required this.label,
    required this.onPressed,
    this.variant = AppButtonVariant.primary,
    this.icon,
    this.isLoading = false,
    this.expanded = true,
    super.key,
  });

  final String label;
  final VoidCallback? onPressed;
  final AppButtonVariant variant;
  final IconData? icon;
  final bool isLoading;
  final bool expanded;

  @override
  Widget build(BuildContext context) {
    final effectiveOnPressed = isLoading ? null : onPressed;

    final Widget button = switch (variant) {
      AppButtonVariant.primary => _GradientButton(
          onPressed: effectiveOnPressed,
          child: _content(context, Colors.white),
        ),
      AppButtonVariant.secondary =>
        OutlinedButton(onPressed: effectiveOnPressed, child: _content(context, context.colors.onSurface)),
      AppButtonVariant.text =>
        TextButton(onPressed: effectiveOnPressed, child: _content(context, context.colors.primary)),
    };

    return expanded ? SizedBox(width: double.infinity, child: button) : button;
  }

  Widget _content(BuildContext context, Color foreground) {
    if (isLoading) {
      return SizedBox(
        height: 20,
        width: 20,
        child: CircularProgressIndicator(strokeWidth: 2.5, color: foreground),
      );
    }

    if (icon != null) {
      return Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 18),
          const SizedBox(width: 8),
          Text(label),
        ],
      );
    }

    return Text(label);
  }
}

/// A pill button painted with the brand gradient and a soft primary glow.
class _GradientButton extends StatelessWidget {
  const _GradientButton({required this.onPressed, required this.child});

  final VoidCallback? onPressed;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    final enabled = onPressed != null;
    return Opacity(
      opacity: enabled ? 1 : 0.5,
      child: DecoratedBox(
        decoration: BoxDecoration(
          borderRadius: AppRadius.pillAll,
          boxShadow: enabled
              ? AppGradients.glow(context.colors.primary, opacity: 0.4, blur: 20)
              : null,
        ),
        child: Material(
          color: Colors.transparent,
          child: Ink(
            decoration: const BoxDecoration(
              gradient: AppGradients.brand,
              borderRadius: AppRadius.pillAll,
            ),
            child: InkWell(
              onTap: onPressed,
              customBorder: const RoundedRectangleBorder(borderRadius: AppRadius.pillAll),
              child: Container(
                constraints: const BoxConstraints(minHeight: 54),
                alignment: Alignment.center,
                child: DefaultTextStyle.merge(
                  style: context.textTheme.labelLarge?.copyWith(color: Colors.white) ??
                      const TextStyle(color: Colors.white, fontWeight: FontWeight.w700),
                  child: IconTheme.merge(
                    data: const IconThemeData(color: Colors.white, size: 18),
                    child: child,
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

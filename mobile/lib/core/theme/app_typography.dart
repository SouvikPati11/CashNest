import 'package:flutter/material.dart';

/// Typography scale for CashNest, layered on the Material 3 type system.
///
/// Returns a [TextTheme] harmonised against the supplied [ColorScheme] so text
/// colors adapt automatically to light/dark. Display and headline styles get
/// tighter tracking and heavier weights for a modern, premium feel.
abstract final class AppTypography {
  const AppTypography._();

  static const String fontFamily = 'Roboto';

  static TextTheme textTheme(ColorScheme scheme) {
    final base = Typography.material2021(platform: TargetPlatform.android).black;
    final onSurface = scheme.onSurface;
    final muted = scheme.onSurfaceVariant;

    return base
        .copyWith(
          displayLarge: base.displayLarge?.copyWith(
            fontWeight: FontWeight.w800,
            letterSpacing: -1.0,
            color: onSurface,
          ),
          displayMedium: base.displayMedium?.copyWith(
            fontWeight: FontWeight.w800,
            letterSpacing: -0.8,
            color: onSurface,
          ),
          displaySmall: base.displaySmall?.copyWith(
            fontWeight: FontWeight.w700,
            letterSpacing: -0.5,
            color: onSurface,
          ),
          headlineMedium: base.headlineMedium?.copyWith(
            fontWeight: FontWeight.w700,
            letterSpacing: -0.4,
            color: onSurface,
          ),
          headlineSmall: base.headlineSmall?.copyWith(
            fontWeight: FontWeight.w700,
            letterSpacing: -0.3,
            color: onSurface,
          ),
          titleLarge: base.titleLarge?.copyWith(
            fontWeight: FontWeight.w700,
            letterSpacing: -0.2,
            color: onSurface,
          ),
          titleMedium: base.titleMedium?.copyWith(
            fontWeight: FontWeight.w600,
            color: onSurface,
          ),
          bodyLarge: base.bodyLarge?.copyWith(color: onSurface, height: 1.4),
          bodyMedium: base.bodyMedium?.copyWith(color: onSurface, height: 1.4),
          bodySmall: base.bodySmall?.copyWith(color: muted, height: 1.35),
          labelLarge: base.labelLarge?.copyWith(
            fontWeight: FontWeight.w700,
            letterSpacing: 0.1,
            color: onSurface,
          ),
        )
        .apply(fontFamily: fontFamily);
  }
}

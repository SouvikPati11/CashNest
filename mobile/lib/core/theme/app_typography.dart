import 'package:flutter/material.dart';

/// Typography scale for CashNest, layered on the Material 3 type system.
///
/// Returns a [TextTheme] harmonised against the supplied [ColorScheme] so text
/// colors adapt automatically to light/dark.
abstract final class AppTypography {
  const AppTypography._();

  static const String fontFamily = 'Roboto';

  static TextTheme textTheme(ColorScheme scheme) {
    final base = Typography.material2021(platform: TargetPlatform.android).black;
    final onSurface = scheme.onSurface;
    final muted = scheme.onSurfaceVariant;

    return base.copyWith(
      displayLarge: base.displayLarge?.copyWith(fontWeight: FontWeight.w700, color: onSurface),
      headlineMedium: base.headlineMedium?.copyWith(fontWeight: FontWeight.w700, color: onSurface),
      titleLarge: base.titleLarge?.copyWith(fontWeight: FontWeight.w600, color: onSurface),
      titleMedium: base.titleMedium?.copyWith(fontWeight: FontWeight.w600, color: onSurface),
      bodyLarge: base.bodyLarge?.copyWith(color: onSurface),
      bodyMedium: base.bodyMedium?.copyWith(color: onSurface),
      bodySmall: base.bodySmall?.copyWith(color: muted),
      labelLarge: base.labelLarge?.copyWith(fontWeight: FontWeight.w600, color: onSurface),
    ).apply(fontFamily: fontFamily);
  }
}

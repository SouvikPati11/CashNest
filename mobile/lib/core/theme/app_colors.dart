import 'package:flutter/material.dart';

/// Brand color tokens for CashNest.
///
/// The seed color drives Material 3 `ColorScheme.fromSeed`; the explicit tokens
/// are available for bespoke surfaces (charts, status chips) that sit outside
/// the generated scheme.
abstract final class AppColors {
  const AppColors._();

  static const Color seed = Color(0xFF4F46E5);

  static const Color primary = Color(0xFF4F46E5);
  static const Color secondary = Color(0xFF42A5F5);
  static const Color accent = Color(0xFFFFC107);

  static const Color success = Color(0xFF2E7D32);
  static const Color warning = Color(0xFFF9A825);
  static const Color danger = Color(0xFFC62828);
  static const Color info = Color(0xFF0288D1);

  static const Color lightBackground = Color(0xFFF6F7FB);
  static const Color lightSurface = Color(0xFFFFFFFF);
  static const Color darkBackground = Color(0xFF121318);
  static const Color darkSurface = Color(0xFF1C1D22);
}

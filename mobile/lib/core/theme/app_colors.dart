import 'package:flutter/material.dart';

/// Brand color tokens for CashNest.
///
/// The [seed] drives Material 3 `ColorScheme.fromSeed` for the light theme; the
/// dark theme is hand-tuned from the explicit surface/brand tokens below to
/// achieve a premium, near-black aesthetic (comparable to Freecash / AttaPoll /
/// Google Opinion Rewards) rather than the muddy auto-generated tonal surfaces.
abstract final class AppColors {
  const AppColors._();

  // ── Brand ────────────────────────────────────────────────────────────────
  static const Color seed = Color(0xFF6D5DF6);

  /// Indigo-violet primary used for CTAs, active states and the brand gradient.
  static const Color primary = Color(0xFF6D5DF6);
  static const Color primaryBright = Color(0xFF8B7BFF);
  static const Color primaryDeep = Color(0xFF4B3FD6);
  static const Color violet = Color(0xFF9B5CF6);
  static const Color magenta = Color(0xFFC13FEF);

  /// Emerald — coins earned, credits, positive deltas.
  static const Color secondary = Color(0xFF22D3A6);
  static const Color secondaryBright = Color(0xFF34D399);

  /// Gold — reward highlights, streaks, premium badges.
  static const Color accent = Color(0xFFFFC93C);
  static const Color accentDeep = Color(0xFFFFB020);

  // ── Semantic ─────────────────────────────────────────────────────────────
  static const Color success = Color(0xFF22C55E);
  static const Color warning = Color(0xFFF9A825);
  static const Color danger = Color(0xFFF43F5E);
  static const Color info = Color(0xFF38BDF8);

  // ── Light surfaces ───────────────────────────────────────────────────────
  static const Color lightBackground = Color(0xFFF5F6FB);
  static const Color lightSurface = Color(0xFFFFFFFF);
  static const Color lightSurfaceAlt = Color(0xFFF0F1F8);
  static const Color lightBorder = Color(0xFFE6E7F0);
  static const Color lightMuted = Color(0xFF6B6D7C);
  static const Color lightOnSurface = Color(0xFF191A25);

  // ── Dark surfaces (premium near-black) ───────────────────────────────────
  static const Color darkBackground = Color(0xFF0B0C11);
  static const Color darkSurface = Color(0xFF15161D);
  static const Color darkSurfaceLow = Color(0xFF111219);
  static const Color darkSurfaceHigh = Color(0xFF1C1E27);
  static const Color darkSurfaceHighest = Color(0xFF23252F);
  static const Color darkBorder = Color(0xFF2A2C38);
  static const Color darkOutline = Color(0xFF3A3C4A);
  static const Color darkMuted = Color(0xFF9A9CAC);
  static const Color darkOnSurface = Color(0xFFECEDF4);
  static const Color darkPrimaryContainer = Color(0xFF2A2560);
  static const Color darkOnPrimaryContainer = Color(0xFFD6D2FF);
}

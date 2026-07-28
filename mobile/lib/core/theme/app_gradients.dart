import 'package:flutter/material.dart';

import 'app_colors.dart';

/// Reusable brand gradients for premium surfaces (balance card, hero CTAs,
/// reward badges). Centralised so every gradient in the app stays on-brand and
/// consistent between light and dark.
abstract final class AppGradients {
  const AppGradients._();

  /// Primary indigo → violet → magenta diagonal. The signature CashNest sweep.
  static const LinearGradient brand = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [AppColors.primaryBright, AppColors.primary, AppColors.violet],
    stops: [0.0, 0.5, 1.0],
  );

  /// Slightly deeper variant for large hero surfaces (balance card).
  static const LinearGradient hero = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [Color(0xFF7C6CFF), AppColors.primary, Color(0xFF7B2FE0)],
    stops: [0.0, 0.55, 1.0],
  );

  /// Emerald sweep — coins, earnings, positive states.
  static const LinearGradient coins = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [AppColors.secondaryBright, AppColors.secondary],
  );

  /// Gold sweep — streaks, rewards, premium badges.
  static const LinearGradient gold = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [AppColors.accent, AppColors.accentDeep],
  );

  /// A soft scrim laid over imagery so overlaid text stays legible.
  static const LinearGradient scrim = LinearGradient(
    begin: Alignment.bottomCenter,
    end: Alignment.topCenter,
    colors: [Color(0xCC000000), Color(0x00000000)],
  );

  /// Subtle top-lit sheen used on dark elevated cards for depth.
  static LinearGradient sheen(Brightness brightness) {
    final isDark = brightness == Brightness.dark;
    return LinearGradient(
      begin: Alignment.topLeft,
      end: Alignment.bottomRight,
      colors: isDark
          ? [Colors.white.withValues(alpha: 0.05), Colors.white.withValues(alpha: 0.0)]
          : [Colors.white.withValues(alpha: 0.9), Colors.white.withValues(alpha: 0.6)],
    );
  }

  /// Coloured glow shadow for a gradient surface of [color].
  static List<BoxShadow> glow(Color color, {double opacity = 0.35, double blur = 28}) {
    return [
      BoxShadow(
        color: color.withValues(alpha: opacity),
        blurRadius: blur,
        offset: const Offset(0, 12),
        spreadRadius: -6,
      ),
    ];
  }
}

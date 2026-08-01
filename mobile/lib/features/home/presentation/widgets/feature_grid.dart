import 'package:flutter/material.dart';

import '../../../../core/theme/app_radius.dart';
import '../../../../core/theme/app_spacing.dart';
import '../../../../shared/extensions/context_extensions.dart';

/// The home "earn" surface, styled after the product spec: a row of quick-access
/// tiles (Surveys · Games · Tasks · Invite) and a two-column grid of colorful
/// gradient feature cards.
///
/// Purely presentational — every tile calls back through [onAction] with a
/// stable action key, reusing the dashboard's existing routing/coming-soon
/// handling. No business logic lives here.

class _Feature {
  const _Feature({
    required this.icon,
    required this.label,
    required this.actionKey,
    required this.colors,
  });

  final IconData icon;
  final String label;
  final String actionKey;
  final List<Color> colors;
}

/// Row of four circular quick-access tiles.
class HomeQuickTiles extends StatelessWidget {
  const HomeQuickTiles({required this.onAction, super.key});

  final void Function(String actionKey) onAction;

  static const List<_Feature> _tiles = [
    _Feature(
      icon: Icons.fact_check_rounded,
      label: 'Surveys',
      actionKey: 'offers',
      colors: [Color(0xFF22C55E), Color(0xFF15A34A)],
    ),
    _Feature(
      icon: Icons.card_giftcard_rounded,
      label: 'Rewards',
      actionKey: 'rewards',
      colors: [Color(0xFFFF9800), Color(0xFFF57C00)],
    ),
    _Feature(
      icon: Icons.task_alt_rounded,
      label: 'Tasks',
      actionKey: 'tasks',
      colors: [Color(0xFF7C3AED), Color(0xFF5B21B6)],
    ),
    _Feature(
      icon: Icons.group_add_rounded,
      label: 'Invite',
      actionKey: 'refer',
      colors: [Color(0xFFF43F5E), Color(0xFFE11D48)],
    ),
  ];

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        for (final t in _tiles)
          Expanded(
            child: _QuickTile(feature: t, onTap: () => onAction(t.actionKey)),
          ),
      ],
    );
  }
}

class _QuickTile extends StatelessWidget {
  const _QuickTile({required this.feature, required this.onTap});

  final _Feature feature;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: AppRadius.lgAll,
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: AppSpacing.sm),
        child: Column(
          children: [
            Container(
              width: 56,
              height: 56,
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: feature.colors,
                ),
                borderRadius: AppRadius.lgAll,
                boxShadow: [
                  BoxShadow(
                    color: feature.colors.first.withValues(alpha: 0.35),
                    blurRadius: 12,
                    offset: const Offset(0, 6),
                    spreadRadius: -4,
                  ),
                ],
              ),
              child: Icon(feature.icon, color: Colors.white, size: 26),
            ),
            const SizedBox(height: AppSpacing.sm),
            Text(
              feature.label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: context.textTheme.labelMedium?.copyWith(fontWeight: FontWeight.w600),
            ),
          ],
        ),
      ),
    );
  }
}

/// Two-column grid of colorful gradient feature cards.
class HomeFeatureGrid extends StatelessWidget {
  const HomeFeatureGrid({required this.onAction, super.key});

  final void Function(String actionKey) onAction;

  // Only features with working screens are shown. Unimplemented spec features
  // (Math Quiz, Read Article, VideoZone, Games) are intentionally omitted until
  // built, rather than shown as "Coming Soon".
  static const List<_Feature> _features = [
    _Feature(
      icon: Icons.event_available_rounded,
      label: 'Daily Checkin',
      actionKey: 'checkin',
      colors: [Color(0xFF00B4DB), Color(0xFF0083B0)],
    ),
    _Feature(
      icon: Icons.local_fire_department_rounded,
      label: 'Hot Offer',
      actionKey: 'tasks',
      colors: [Color(0xFF3A1C71), Color(0xFF4B3FD6)],
    ),
    _Feature(
      icon: Icons.card_giftcard_rounded,
      label: 'Scratch Card',
      actionKey: 'scratch',
      colors: [Color(0xFFF7971E), Color(0xFFFFA751)],
    ),
    _Feature(
      icon: Icons.casino_rounded,
      label: 'Spin & Win',
      actionKey: 'spin',
      colors: [Color(0xFF7B2FE0), Color(0xFF9B5CF6)],
    ),
    _Feature(
      icon: Icons.fact_check_rounded,
      label: 'Surveys & Offers',
      actionKey: 'offers',
      colors: [Color(0xFF12C2E9), Color(0xFF2E86DE)],
    ),
    _Feature(
      icon: Icons.redeem_rounded,
      label: 'Redeem',
      actionKey: 'withdraw',
      colors: [Color(0xFFFFB020), Color(0xFFFF7A00)],
    ),
  ];

  @override
  Widget build(BuildContext context) {
    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      mainAxisSpacing: AppSpacing.md,
      crossAxisSpacing: AppSpacing.md,
      childAspectRatio: 1.7,
      children: [
        for (final f in _features)
          _FeatureCard(feature: f, onTap: () => onAction(f.actionKey)),
      ],
    );
  }
}

class _FeatureCard extends StatelessWidget {
  const _FeatureCard({required this.feature, required this.onTap});

  final _Feature feature;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        borderRadius: AppRadius.lgAll,
        boxShadow: [
          BoxShadow(
            color: feature.colors.first.withValues(alpha: 0.30),
            blurRadius: 16,
            offset: const Offset(0, 8),
            spreadRadius: -6,
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: AppRadius.lgAll,
        child: Stack(
          children: [
            Positioned.fill(
              child: DecoratedBox(
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: feature.colors,
                  ),
                ),
              ),
            ),
            Positioned(
              right: -14,
              bottom: -14,
              child: Icon(
                feature.icon,
                size: 78,
                color: Colors.white.withValues(alpha: 0.16),
              ),
            ),
            Material(
              type: MaterialType.transparency,
              child: InkWell(
                onTap: onTap,
                child: Padding(
                  padding: const EdgeInsets.all(AppSpacing.md),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Container(
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.22),
                          borderRadius: AppRadius.smAll,
                        ),
                        child: Icon(feature.icon, color: Colors.white, size: 20),
                      ),
                      Text(
                        feature.label,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: context.textTheme.titleSmall?.copyWith(
                          color: Colors.white,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

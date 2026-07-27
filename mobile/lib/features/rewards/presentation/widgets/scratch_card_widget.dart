import 'package:flutter/material.dart';

import '../../../../core/theme/app_radius.dart';
import '../../../../core/theme/app_spacing.dart';
import '../../../../shared/extensions/context_extensions.dart';
import '../../l10n/rewards_strings.dart';
import '../../resources/reward_visuals.dart';

/// An interactive scratch-off card. The [rewardCoins] underneath is already
/// known (the card is revealed server-side before this is shown); scratching is
/// the reveal animation. Calls [onCompleted] once enough of the foil is removed.
class ScratchCardWidget extends StatefulWidget {
  const ScratchCardWidget({
    required this.rewardCoins,
    required this.onCompleted,
    this.size = 260,
    super.key,
  });

  final int rewardCoins;
  final VoidCallback onCompleted;
  final double size;

  @override
  State<ScratchCardWidget> createState() => _ScratchCardWidgetState();
}

class _ScratchCardWidgetState extends State<ScratchCardWidget>
    with SingleTickerProviderStateMixin {
  final List<Offset?> _points = [];
  double _distance = 0;
  Offset? _last;
  bool _completed = false;

  late final AnimationController _fade = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 350),
  );

  @override
  void dispose() {
    _fade.dispose();
    super.dispose();
  }

  void _onPan(Offset local) {
    if (_completed) {
      return;
    }
    final last = _last;
    if (last != null) {
      _distance += (local - last).distance;
    }
    _last = local;
    setState(() => _points.add(local));

    // Reveal once the user has scratched roughly across the card a few times.
    if (_distance > widget.size * 2.2) {
      _complete();
    }
  }

  void _complete() {
    _completed = true;
    _fade.forward();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) {
        widget.onCompleted();
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final s = RewardsStrings.of(context);
    final localeCode = Localizations.localeOf(context).languageCode;

    return ClipRRect(
      borderRadius: AppRadius.lgAll,
      child: SizedBox(
        width: widget.size,
        height: widget.size,
        child: Stack(
          fit: StackFit.expand,
          children: [
            // Reward underneath.
            DecoratedBox(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [
                    context.colors.primaryContainer,
                    context.colors.tertiaryContainer,
                  ],
                ),
              ),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.monetization_on, size: 56, color: context.colors.primary),
                  const SizedBox(height: AppSpacing.sm),
                  Text(
                    '${RewardVisuals.coins(widget.rewardCoins, localeCode: localeCode)} ${s.coins}',
                    style: context.textTheme.headlineSmall?.copyWith(
                      fontWeight: FontWeight.w800,
                      color: context.colors.onPrimaryContainer,
                    ),
                  ),
                ],
              ),
            ),
            // Scratchable foil.
            FadeTransition(
              opacity: Tween<double>(begin: 1, end: 0).animate(_fade),
              child: GestureDetector(
                onPanStart: (d) => _onPan(d.localPosition),
                onPanUpdate: (d) => _onPan(d.localPosition),
                onPanEnd: (_) => _last = null,
                child: CustomPaint(
                  painter: _ScratchPainter(
                    points: _points,
                    foilColor: context.colors.surfaceContainerHighest,
                  ),
                  child: Center(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.touch_app_outlined,
                            size: 40, color: context.colors.onSurfaceVariant),
                        const SizedBox(height: AppSpacing.sm),
                        Text(
                          s.scratchToReveal,
                          style: context.textTheme.titleSmall
                              ?.copyWith(color: context.colors.onSurfaceVariant),
                        ),
                      ],
                    ),
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

class _ScratchPainter extends CustomPainter {
  _ScratchPainter({required this.points, required this.foilColor});

  final List<Offset?> points;
  final Color foilColor;

  @override
  void paint(Canvas canvas, Size size) {
    final rect = Offset.zero & size;
    canvas.saveLayer(rect, Paint());
    canvas.drawRect(rect, Paint()..color = foilColor);

    final clear = Paint()
      ..blendMode = BlendMode.clear
      ..style = PaintingStyle.stroke
      ..strokeWidth = 40
      ..strokeCap = StrokeCap.round
      ..strokeJoin = StrokeJoin.round;

    final path = Path();
    var penDown = false;
    for (final point in points) {
      if (point == null) {
        penDown = false;
        continue;
      }
      if (!penDown) {
        path.moveTo(point.dx, point.dy);
        penDown = true;
      } else {
        path.lineTo(point.dx, point.dy);
      }
    }
    canvas.drawPath(path, clear);
    canvas.restore();
  }

  @override
  bool shouldRepaint(covariant _ScratchPainter oldDelegate) =>
      oldDelegate.points.length != points.length || oldDelegate.foilColor != foilColor;
}

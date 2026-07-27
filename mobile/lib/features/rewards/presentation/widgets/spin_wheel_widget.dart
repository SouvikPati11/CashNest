import 'dart:math' as math;

import 'package:flutter/material.dart';

import '../../../../shared/extensions/context_extensions.dart';
import '../../models/spin_status.dart';
import '../../resources/reward_visuals.dart';

/// A spin wheel that animates to a server-chosen segment.
///
/// The parent increments [spinToken] (and sets [targetIndex]) to trigger a spin;
/// [onSettled] fires when the wheel stops on the target. The wheel never decides
/// the outcome — it only animates to it.
class SpinWheelWidget extends StatefulWidget {
  const SpinWheelWidget({
    required this.segments,
    required this.spinToken,
    required this.targetIndex,
    required this.onSettled,
    this.size = 280,
    super.key,
  });

  final List<SpinSegment> segments;
  final int spinToken;
  final int? targetIndex;
  final VoidCallback onSettled;
  final double size;

  @override
  State<SpinWheelWidget> createState() => _SpinWheelWidgetState();
}

class _SpinWheelWidgetState extends State<SpinWheelWidget>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 3600),
  )..addStatusListener(_onStatus);

  double _rotation = 0;
  Animation<double>? _animation;

  @override
  void didUpdateWidget(covariant SpinWheelWidget oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.spinToken != oldWidget.spinToken && widget.targetIndex != null) {
      _spinTo(widget.targetIndex!);
    }
  }

  void _spinTo(int index) {
    final count = widget.segments.length;
    if (count == 0) {
      widget.onSettled();
      return;
    }
    final seg = 2 * math.pi / count;
    const turns = 5;
    // Bring segment center under the top pointer, plus several full turns.
    final target = (turns * 2 * math.pi) - (index * seg + seg / 2);

    _animation = Tween<double>(begin: _rotation % (2 * math.pi), end: target).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeOutCubic),
    );
    _controller
      ..reset()
      ..forward();
  }

  void _onStatus(AnimationStatus status) {
    if (status == AnimationStatus.completed) {
      _rotation = _animation?.value ?? _rotation;
      widget.onSettled();
    }
  }

  @override
  void dispose() {
    _controller
      ..removeStatusListener(_onStatus)
      ..dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: widget.size,
      height: widget.size,
      child: Stack(
        alignment: Alignment.center,
        children: [
          AnimatedBuilder(
            animation: _controller,
            builder: (context, child) {
              final angle = _animation?.value ?? _rotation;
              return Transform.rotate(
                angle: angle,
                child: CustomPaint(
                  size: Size.square(widget.size),
                  painter: _WheelPainter(
                    segments: widget.segments,
                    borderColor: context.colors.surface,
                  ),
                ),
              );
            },
          ),
          // Hub.
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: context.colors.surface,
              shape: BoxShape.circle,
              border: Border.all(color: context.colors.primary, width: 3),
            ),
            child: Icon(Icons.star, color: context.colors.primary, size: 22),
          ),
          // Top pointer.
          Positioned(
            top: 0,
            child: Icon(Icons.arrow_drop_down, size: 48, color: context.colors.error),
          ),
        ],
      ),
    );
  }
}

class _WheelPainter extends CustomPainter {
  _WheelPainter({required this.segments, required this.borderColor});

  final List<SpinSegment> segments;
  final Color borderColor;

  @override
  void paint(Canvas canvas, Size size) {
    final count = segments.length;
    if (count == 0) {
      return;
    }
    final center = Offset(size.width / 2, size.height / 2);
    final radius = size.width / 2;
    final rect = Rect.fromCircle(center: center, radius: radius);
    final seg = 2 * math.pi / count;
    const topStart = -math.pi / 2; // 12 o'clock

    final border = Paint()
      ..style = PaintingStyle.stroke
      ..strokeWidth = 2
      ..color = borderColor;

    for (var i = 0; i < count; i++) {
      final start = topStart + i * seg;
      final fill = Paint()
        ..style = PaintingStyle.fill
        ..color = RewardVisuals.hexToColor(segments[i].colorHex);
      canvas
        ..drawArc(rect, start, seg, true, fill)
        ..drawArc(rect, start, seg, true, border);

      _drawLabel(canvas, center, radius, start + seg / 2, segments[i].label);
    }

    canvas.drawCircle(center, radius, border..strokeWidth = 4);
  }

  void _drawLabel(Canvas canvas, Offset center, double radius, double angle, String label) {
    final painter = TextPainter(
      text: TextSpan(
        text: label,
        style: const TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.w700),
      ),
      textDirection: TextDirection.ltr,
    )..layout();

    final r = radius * 0.66;
    final pos = Offset(
      center.dx + r * math.cos(angle) - painter.width / 2,
      center.dy + r * math.sin(angle) - painter.height / 2,
    );
    painter.paint(canvas, pos);
  }

  @override
  bool shouldRepaint(covariant _WheelPainter oldDelegate) =>
      oldDelegate.segments != segments || oldDelegate.borderColor != borderColor;
}

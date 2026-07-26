import 'package:flutter/widgets.dart';

/// Device size classes derived from the shortest layout width.
enum DeviceType { phone, tablet, desktop }

/// Responsive breakpoints (logical pixels).
abstract final class Breakpoints {
  const Breakpoints._();

  static const double tablet = 600;
  static const double desktop = 1024;

  static DeviceType typeForWidth(double width) {
    if (width >= desktop) {
      return DeviceType.desktop;
    }
    if (width >= tablet) {
      return DeviceType.tablet;
    }
    return DeviceType.phone;
  }
}

/// Context helpers for responsive decisions.
extension ResponsiveContext on BuildContext {
  double get screenWidth => MediaQuery.sizeOf(this).width;

  DeviceType get deviceType => Breakpoints.typeForWidth(screenWidth);

  bool get isPhone => deviceType == DeviceType.phone;

  bool get isTablet => deviceType == DeviceType.tablet;

  bool get isDesktop => deviceType == DeviceType.desktop;
}

/// Chooses the appropriate builder for the current width. `tablet`/`desktop`
/// fall back to smaller variants when not supplied, so callers only implement
/// the layouts they need.
class ResponsiveLayout extends StatelessWidget {
  const ResponsiveLayout({
    required this.phone,
    this.tablet,
    this.desktop,
    super.key,
  });

  final WidgetBuilder phone;
  final WidgetBuilder? tablet;
  final WidgetBuilder? desktop;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        final type = Breakpoints.typeForWidth(constraints.maxWidth);
        return switch (type) {
          DeviceType.desktop => (desktop ?? tablet ?? phone)(context),
          DeviceType.tablet => (tablet ?? phone)(context),
          DeviceType.phone => phone(context),
        };
      },
    );
  }
}

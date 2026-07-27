import 'package:flutter/widgets.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/firebase/push_notification_manager.dart';
import '../../features/notification/providers/notification_providers.dart';
import '../di/providers.dart';
import '../router/app_router.dart';

/// Owns app-lifecycle side effects and push-notification wiring for the whole
/// app (tasks 2, 5, 17, 18):
///
///  - starts the [PushNotificationManager] once (guarded on Firebase readiness),
///    routing notification taps through the app router and refreshing the unread
///    badge on foreground messages;
///  - on **resume**, refreshes the unread badge and logs an analytics event.
///
/// Placed above `MaterialApp.router` so the observer is app-global.
class AppLifecycleReactor extends ConsumerStatefulWidget {
  const AppLifecycleReactor({required this.child, super.key});

  final Widget child;

  @override
  ConsumerState<AppLifecycleReactor> createState() => _AppLifecycleReactorState();
}

class _AppLifecycleReactorState extends ConsumerState<AppLifecycleReactor>
    with WidgetsBindingObserver {
  PushNotificationManager? _push;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    WidgetsBinding.instance.addPostFrameCallback((_) => _startMessaging());
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _push?.dispose();
    super.dispose();
  }

  Future<void> _startMessaging() async {
    // Only wire FCM when Firebase actually initialized; otherwise the app boots
    // cleanly without it (offline / unconfigured builds).
    if (!ref.read(firebaseReadyProvider)) {
      return;
    }
    final manager = PushNotificationManager(
      messaging: ref.read(pushMessagingServiceProvider),
      logger: ref.read(appLoggerProvider),
      onNavigate: (location) {
        if (mounted) {
          ref.read(appRouterProvider).push(location);
        }
      },
      onForegroundMessage: (_) {
        if (mounted) {
          ref.invalidate(unreadCountProvider);
        }
      },
    );
    _push = manager;
    await manager.start();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      ref.invalidate(unreadCountProvider);
      ref.read(analyticsServiceProvider).logEvent('app_resumed');
    }
  }

  @override
  Widget build(BuildContext context) => widget.child;
}

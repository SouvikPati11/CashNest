import 'package:flutter_riverpod/flutter_riverpod.dart';

/// Application readiness flag that gates the splash → home transition.
///
/// Bootstrap has already initialized services before `runApp`; the splash screen
/// flips this to `true` after its minimum display window, and the router reacts.
final appStartupProvider = StateProvider<bool>((ref) => false);

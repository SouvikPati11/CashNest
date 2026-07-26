import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../app/di/providers.dart';
import '../../auth/providers/auth_providers.dart';
import '../application/home_controller.dart';
import '../data/home_repository.dart';
import '../data/home_repository_impl.dart';
import '../models/home_data.dart';

/// Riverpod wiring for the home module. Reuses the foundation API client and the
/// authentication module's user state (neither is modified).

final homeRepositoryProvider = Provider<HomeRepository>(
  (ref) => HomeRepositoryImpl(ref.watch(apiClientProvider)),
);

final homeControllerProvider =
    StateNotifierProvider<HomeController, AsyncValue<HomeData>>(
  (ref) => HomeController(
    ref.watch(homeRepositoryProvider),
    () => ref.read(authControllerProvider).user,
  ),
);

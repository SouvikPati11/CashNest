import 'package:connectivity_plus/connectivity_plus.dart';

/// Connectivity abstraction over `connectivity_plus`.
///
/// Note: connectivity indicates a network interface exists, not guaranteed
/// internet reachability — treat it as a fast pre-check, with real errors still
/// mapped from failed requests.
class ConnectivityService {
  ConnectivityService({Connectivity? connectivity})
      : _connectivity = connectivity ?? Connectivity();

  final Connectivity _connectivity;

  /// True when at least one non-`none` connectivity result is present.
  Future<bool> isOnline() async {
    final results = await _connectivity.checkConnectivity();
    return _isOnline(results);
  }

  /// Emits `true`/`false` as connectivity changes (for the offline banner).
  Stream<bool> get onStatusChanged =>
      _connectivity.onConnectivityChanged.map(_isOnline);

  bool _isOnline(List<ConnectivityResult> results) =>
      results.any((result) => result != ConnectivityResult.none);
}

import 'app/bootstrap.dart';

/// CashNest application entry point.
///
/// All initialization lives in [bootstrap] so `main` stays trivial and the app
/// launches inside a guarded error zone.
Future<void> main() async {
  await bootstrap();
}

import 'dart:math';

/// Generates client idempotency keys for the withdrawal request mutation
/// (`X-Idempotency-Key`, required by the endpoint), so an accidental retry
/// returns the original result instead of creating a duplicate hold.
abstract final class IdempotencyKey {
  const IdempotencyKey._();

  static final Random _random = Random();

  static String generate([String prefix = 'wd']) {
    final ts = DateTime.now().microsecondsSinceEpoch;
    final suffix = _random.nextInt(0x10000000).toRadixString(16).padLeft(7, '0');
    return '${prefix}_${ts}_$suffix';
  }
}

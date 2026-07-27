import 'dart:math';

/// Generates client idempotency keys for reward-claiming mutations
/// (`X-Idempotency-Key`), so accidental retries don't double-submit.
///
/// The backend is the source of truth for single-crediting (via ledger
/// `reference_id`); this key just lets a retried request return the original
/// result instead of erroring.
abstract final class IdempotencyKey {
  const IdempotencyKey._();

  static final Random _random = Random();

  /// A reasonably-unique key, e.g. `cn_1753632000000_a3f9c1`.
  static String generate([String prefix = 'cn']) {
    final ts = DateTime.now().microsecondsSinceEpoch;
    final suffix = _random.nextInt(0x10000000).toRadixString(16).padLeft(7, '0');
    return '${prefix}_${ts}_$suffix';
  }
}

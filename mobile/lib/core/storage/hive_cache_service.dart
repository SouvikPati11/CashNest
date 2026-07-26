import 'dart:async';
import 'dart:convert';

import 'package:hive/hive.dart';

/// Lightweight JSON cache backed by a Hive box.
///
/// Stores serialisable maps/lists with an optional TTL. Expired entries are
/// evicted lazily on read. Intended for caching API responses and small
/// documents (feature repositories decide what to cache).
class HiveCacheService {
  HiveCacheService(this._box);

  final Box<String> _box;

  static const String _valueKey = 'v';
  static const String _expiresKey = 'e';

  /// Store [value] (a JSON-encodable structure) under [key] with optional [ttl].
  Future<void> put(String key, Object value, {Duration? ttl}) async {
    final envelope = <String, dynamic>{
      _valueKey: value,
      _expiresKey: ttl == null ? null : DateTime.now().add(ttl).millisecondsSinceEpoch,
    };
    await _box.put(key, jsonEncode(envelope));
  }

  /// Read a previously cached value, or null when absent/expired.
  dynamic get(String key) {
    final raw = _box.get(key);
    if (raw == null) {
      return null;
    }

    final decoded = jsonDecode(raw);
    if (decoded is! Map<String, dynamic>) {
      return null;
    }

    final expiresAt = decoded[_expiresKey];
    if (expiresAt is int && DateTime.now().millisecondsSinceEpoch > expiresAt) {
      unawaited(_box.delete(key));
      return null;
    }

    return decoded[_valueKey];
  }

  bool has(String key) => get(key) != null;

  Future<void> remove(String key) => _box.delete(key);

  Future<void> clear() => _box.clear();
}

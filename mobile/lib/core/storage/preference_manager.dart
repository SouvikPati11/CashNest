import 'package:hive/hive.dart';

/// Typed, non-sensitive preference store backed by a Hive box.
///
/// Used for user choices such as theme mode, locale, and onboarding state.
/// Sensitive data (tokens) belongs in [SecureStorageService], not here.
class PreferenceManager {
  PreferenceManager(this._box);

  final Box<dynamic> _box;

  String? getString(String key) {
    final value = _box.get(key);
    return value is String ? value : null;
  }

  Future<void> setString(String key, String value) => _box.put(key, value);

  bool getBool(String key, {bool defaultValue = false}) {
    final value = _box.get(key);
    return value is bool ? value : defaultValue;
  }

  Future<void> setBool(String key, {required bool value}) => _box.put(key, value);

  int? getInt(String key) {
    final value = _box.get(key);
    return value is int ? value : null;
  }

  Future<void> setInt(String key, int value) => _box.put(key, value);

  bool has(String key) => _box.containsKey(key);

  Future<void> remove(String key) => _box.delete(key);

  Future<void> clear() => _box.clear();
}

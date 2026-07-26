import 'package:cashnest/core/config/app_config.dart';
import 'package:cashnest/core/config/app_environment.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('AppConfig', () {
    test('resolves development defaults', () {
      final config = AppConfig.resolve();

      expect(config.environment, AppEnvironment.development);
      expect(config.apiBaseUrl, isNotEmpty);
      expect(config.enableNetworkLogging, isTrue);
    });

    test('copyWith overrides selected fields only', () {
      final config = AppConfig.resolve();
      final updated = config.copyWith(apiBaseUrl: 'https://example.test/v1');

      expect(updated.apiBaseUrl, 'https://example.test/v1');
      expect(updated.environment, config.environment);
      expect(updated.connectTimeout, config.connectTimeout);
    });
  });

  group('AppEnvironment', () {
    test('resolve falls back to development for unknown values', () {
      expect(AppEnvironment.resolve(), AppEnvironment.development);
    });
  });
}

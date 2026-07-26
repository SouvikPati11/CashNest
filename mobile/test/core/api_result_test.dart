import 'package:cashnest/core/error/app_exception.dart';
import 'package:cashnest/core/network/api_result.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('ApiResult', () {
    test('success exposes data and folds correctly', () {
      const ApiResult<int> result = ApiResult.success(42);

      expect(result.isSuccess, isTrue);
      expect(result.isFailure, isFalse);
      expect(result.dataOrNull, 42);
      expect(result.when(success: (d) => d * 2, failure: (_) => -1), 84);
    });

    test('failure exposes error and folds correctly', () {
      const ApiResult<int> result = ApiResult.failure(NetworkException('offline'));

      expect(result.isFailure, isTrue);
      expect(result.dataOrNull, isNull);
      expect(result.when(success: (_) => 'ok', failure: (e) => e.message), 'offline');
    });

    test('map transforms success and preserves failure', () {
      const ApiResult<int> success = ApiResult.success(2);
      const ApiResult<int> failure = ApiResult.failure(CacheException('bad'));

      expect(success.map((v) => v + 1).dataOrNull, 3);
      expect(failure.map((v) => v + 1).isFailure, isTrue);
    });
  });
}

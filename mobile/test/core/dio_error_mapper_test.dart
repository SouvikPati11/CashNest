import 'package:cashnest/core/error/app_exception.dart';
import 'package:cashnest/core/network/dio_error_mapper.dart';
import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  final options = RequestOptions(path: '/x');

  DioException badResponse(int status, Object? data) => DioException(
        requestOptions: options,
        type: DioExceptionType.badResponse,
        response: Response<dynamic>(requestOptions: options, statusCode: status, data: data),
      );

  group('DioErrorMapper', () {
    test('maps timeouts', () {
      final result = DioErrorMapper.map(
        DioException(requestOptions: options, type: DioExceptionType.connectionTimeout),
      );
      expect(result, isA<TimeoutException>());
    });

    test('maps connection errors to network', () {
      final result = DioErrorMapper.map(
        DioException(requestOptions: options, type: DioExceptionType.connectionError),
      );
      expect(result, isA<NetworkException>());
    });

    test('maps 401 to unauthorized with server message', () {
      final result = DioErrorMapper.map(badResponse(401, {'message': 'expired'}));
      expect(result, isA<UnauthorizedException>());
      expect(result.message, 'expired');
    });

    test('maps 422 to validation with field errors', () {
      final result = DioErrorMapper.map(badResponse(422, {
        'message': 'invalid',
        'errors': [
          {'field': 'email', 'message': 'required', 'code': 'VALIDATION_ERROR'},
        ],
      }));

      expect(result, isA<ValidationException>());
      final validation = result as ValidationException;
      expect(validation.fieldErrors['email'], contains('required'));
    });

    test('maps other statuses to server exception', () {
      final result = DioErrorMapper.map(badResponse(500, {'message': 'boom'}));
      expect(result, isA<ServerException>());
      expect((result as ServerException).statusCode, 500);
    });
  });
}

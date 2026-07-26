import '../error/app_exception.dart';

/// A success/failure result type for repository/service calls.
///
/// Encourages explicit error handling at call sites instead of try/catch noise.
/// Uses Dart 3 sealed classes + pattern matching (no code generation required).
sealed class ApiResult<T> {
  const ApiResult();

  const factory ApiResult.success(T data) = ApiSuccess<T>;

  const factory ApiResult.failure(AppException error) = ApiFailure<T>;

  bool get isSuccess => this is ApiSuccess<T>;

  bool get isFailure => this is ApiFailure<T>;

  /// The value on success, or null on failure.
  T? get dataOrNull => switch (this) {
        ApiSuccess<T>(:final data) => data,
        ApiFailure<T>() => null,
      };

  /// Fold both branches into a single value.
  R when<R>({
    required R Function(T data) success,
    required R Function(AppException error) failure,
  }) {
    return switch (this) {
      ApiSuccess<T>(:final data) => success(data),
      ApiFailure<T>(:final error) => failure(error),
    };
  }

  /// Transform the success value, preserving a failure unchanged.
  ApiResult<R> map<R>(R Function(T data) transform) {
    return switch (this) {
      ApiSuccess<T>(:final data) => ApiResult<R>.success(transform(data)),
      ApiFailure<T>(:final error) => ApiResult<R>.failure(error),
    };
  }
}

final class ApiSuccess<T> extends ApiResult<T> {
  const ApiSuccess(this.data);

  final T data;
}

final class ApiFailure<T> extends ApiResult<T> {
  const ApiFailure(this.error);

  final AppException error;
}

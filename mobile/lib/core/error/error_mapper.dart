import '../localization/app_localizations.dart';
import 'app_exception.dart';

/// Maps [AppException]s to human-readable, localized messages for the UI.
abstract final class ErrorMapper {
  const ErrorMapper._();

  static String toMessage(Object error, AppLocalizations l10n) {
    if (error is ValidationException) {
      return error.message.isNotEmpty ? error.message : l10n.errorValidation;
    }
    if (error is UnauthorizedException) {
      // Prefer the server's message (e.g. "Invalid email or password.") so real
      // 401 responses are shown instead of a generic string.
      return error.message.isNotEmpty ? error.message : l10n.errorUnauthorized;
    }
    if (error is NetworkException) {
      return l10n.errorNetwork;
    }
    if (error is TimeoutException) {
      return l10n.errorTimeout;
    }
    if (error is ServerException) {
      return error.message.isNotEmpty ? error.message : l10n.errorServer;
    }
    if (error is CacheException) {
      return l10n.errorGeneric;
    }
    if (error is AppException) {
      return error.message.isNotEmpty ? error.message : l10n.errorGeneric;
    }
    return l10n.errorGeneric;
  }
}

import 'package:cashnest/core/error/app_exception.dart';
import 'package:cashnest/core/error/error_mapper.dart';
import 'package:cashnest/core/localization/app_localizations.dart';
import 'package:cashnest/core/localization/l10n/strings_en.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  const l10n = AppLocalizations(Locale('en'), stringsEn);

  group('ErrorMapper', () {
    test('maps network exception', () {
      expect(ErrorMapper.toMessage(const NetworkException(''), l10n), l10n.errorNetwork);
    });

    test('maps timeout exception', () {
      expect(ErrorMapper.toMessage(const TimeoutException(''), l10n), l10n.errorTimeout);
    });

    test('maps unauthorized exception', () {
      expect(ErrorMapper.toMessage(const UnauthorizedException(''), l10n), l10n.errorUnauthorized);
    });

    test('prefers server message when present', () {
      final message = ErrorMapper.toMessage(
        const ServerException('custom', statusCode: 500),
        l10n,
      );
      expect(message, 'custom');
    });

    test('falls back to generic for unknown errors', () {
      expect(ErrorMapper.toMessage(Exception('x'), l10n), l10n.errorGeneric);
    });
  });
}

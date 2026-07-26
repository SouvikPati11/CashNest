import 'package:cashnest/core/responsive/responsive.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('Breakpoints.typeForWidth', () {
    test('classifies phone widths', () {
      expect(Breakpoints.typeForWidth(320), DeviceType.phone);
      expect(Breakpoints.typeForWidth(599), DeviceType.phone);
    });

    test('classifies tablet widths', () {
      expect(Breakpoints.typeForWidth(600), DeviceType.tablet);
      expect(Breakpoints.typeForWidth(1023), DeviceType.tablet);
    });

    test('classifies desktop widths', () {
      expect(Breakpoints.typeForWidth(1024), DeviceType.desktop);
      expect(Breakpoints.typeForWidth(1920), DeviceType.desktop);
    });
  });
}

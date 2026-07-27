import 'package:cashnest/core/deeplink/deep_link_resolver.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('DeepLinkResolver.resolve', () {
    test('maps bare keys to routes', () {
      expect(DeepLinkResolver.resolve('wallet'), '/wallet');
      expect(DeepLinkResolver.resolve('rewards'), '/rewards');
      expect(DeepLinkResolver.resolve('inbox'), '/notifications');
      expect(DeepLinkResolver.resolve('offerwall'), '/earn');
    });

    test('maps app paths', () {
      expect(DeepLinkResolver.resolve('/wallet'), '/wallet');
      expect(DeepLinkResolver.resolve('/withdraw'), '/withdraw');
    });

    test('maps custom-scheme and https deep links', () {
      expect(DeepLinkResolver.resolve('cashnest://wallet'), '/wallet');
      expect(DeepLinkResolver.resolve('https://cashnest.app/referral'), '/referral');
      expect(DeepLinkResolver.resolve('https://cashnest.app/notifications?id=1'), '/notifications');
    });

    test('is case-insensitive', () {
      expect(DeepLinkResolver.resolve('WALLET'), '/wallet');
      expect(DeepLinkResolver.resolve('cashnest://Earn'), '/earn');
    });

    test('returns null for unknown or empty links', () {
      expect(DeepLinkResolver.resolve(null), isNull);
      expect(DeepLinkResolver.resolve(''), isNull);
      expect(DeepLinkResolver.resolve('   '), isNull);
      expect(DeepLinkResolver.resolve('https://cashnest.app/unknown'), isNull);
    });
  });
}

/// Resolves an inbound deep link (from an FCM notification payload, an app link,
/// or a custom-scheme URI) into an in-app router location.
///
/// Accepts many shapes and normalizes them:
///  - a bare key: `wallet`
///  - an app path: `/wallet`
///  - a custom scheme: `cashnest://wallet`
///  - an https app link: `https://cashnest.app/wallet`
///
/// Unknown links resolve to `null` so the caller can ignore them safely.
abstract final class DeepLinkResolver {
  const DeepLinkResolver._();

  static const Map<String, String> _routes = {
    'home': '/home',
    'rewards': '/rewards',
    'wallet': '/wallet',
    'earn': '/earn',
    'offerwall': '/earn',
    'offers': '/earn',
    'referral': '/referral',
    'refer': '/referral',
    'withdraw': '/withdraw',
    'notifications': '/notifications',
    'inbox': '/notifications',
    'settings': '/settings',
  };

  /// Map [raw] to a known router location, or `null` when unrecognized.
  static String? resolve(String? raw) {
    final value = raw?.trim();
    if (value == null || value.isEmpty) {
      return null;
    }

    final uri = Uri.tryParse(value);
    final candidates = <String>[];
    if (uri != null) {
      if (uri.host.isNotEmpty) {
        candidates.add(uri.host);
      }
      candidates.addAll(uri.pathSegments);
    }
    // Also consider the raw value stripped of a leading slash.
    candidates.add(value.startsWith('/') ? value.substring(1) : value);

    for (final candidate in candidates) {
      final key = candidate.toLowerCase();
      final route = _routes[key];
      if (route != null) {
        return route;
      }
    }
    return null;
  }
}

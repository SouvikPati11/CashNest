import 'package:flutter/foundation.dart';

/// The result of recording an offer click (`POST /offerwall/offers/{uuid}/click`).
@immutable
class ClickResult {
  const ClickResult({required this.redirectUrl, this.clickToken});

  final String redirectUrl;
  final String? clickToken;

  factory ClickResult.fromJson(Map<String, dynamic> json) {
    return ClickResult(
      redirectUrl: json['redirect_url'] as String? ?? '',
      clickToken: json['click_token'] as String?,
    );
  }
}

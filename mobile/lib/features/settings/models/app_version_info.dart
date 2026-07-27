import 'package:flutter/foundation.dart';

/// Latest-version + force-update info (`GET /v1/app/version`).
@immutable
class AppVersionInfo {
  const AppVersionInfo({
    required this.latestVersion,
    this.platform,
    this.latestVersionCode,
    this.minSupportedCode,
    this.forceUpdate = false,
    this.updateAvailable = false,
    this.storeUrl,
    this.changelog,
  });

  final String latestVersion;
  final String? platform;
  final int? latestVersionCode;
  final int? minSupportedCode;
  final bool forceUpdate;
  final bool updateAvailable;
  final String? storeUrl;
  final String? changelog;

  static int? _intOrNull(dynamic v) => v == null ? null : (v as num?)?.toInt();

  factory AppVersionInfo.fromJson(Map<String, dynamic> json) {
    return AppVersionInfo(
      latestVersion: json['latest_version'] as String? ?? '',
      platform: json['platform'] as String?,
      latestVersionCode: _intOrNull(json['latest_version_code']),
      minSupportedCode: _intOrNull(json['min_supported_code']),
      forceUpdate: json['force_update'] as bool? ?? false,
      updateAvailable: json['update_available'] as bool? ?? false,
      storeUrl: json['store_url'] as String?,
      changelog: json['changelog'] as String?,
    );
  }
}

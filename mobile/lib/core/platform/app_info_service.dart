import 'package:device_info_plus/device_info_plus.dart';
import 'package:flutter/foundation.dart';
import 'package:package_info_plus/package_info_plus.dart';

/// Snapshot of app + device metadata (for headers, diagnostics, support).
@immutable
class AppInfo {
  const AppInfo({
    required this.appName,
    required this.version,
    required this.buildNumber,
    required this.platform,
    required this.deviceModel,
    required this.osVersion,
  });

  final String appName;
  final String version;
  final String buildNumber;
  final String platform;
  final String deviceModel;
  final String osVersion;
}

/// Resolves and caches [AppInfo] from `package_info_plus` + `device_info_plus`.
class AppInfoService {
  AppInfoService({DeviceInfoPlugin? deviceInfo}) : _deviceInfo = deviceInfo ?? DeviceInfoPlugin();

  final DeviceInfoPlugin _deviceInfo;
  AppInfo? _cached;

  AppInfo? get current => _cached;

  Future<AppInfo> load() async {
    if (_cached != null) {
      return _cached!;
    }

    final package = await PackageInfo.fromPlatform();
    final (model, os, platform) = await _device();

    return _cached = AppInfo(
      appName: package.appName,
      version: package.version,
      buildNumber: package.buildNumber,
      platform: platform,
      deviceModel: model,
      osVersion: os,
    );
  }

  Future<(String model, String os, String platform)> _device() async {
    try {
      if (defaultTargetPlatform == TargetPlatform.android) {
        final info = await _deviceInfo.androidInfo;
        return (info.model, 'Android ${info.version.release}', 'android');
      }
      if (defaultTargetPlatform == TargetPlatform.iOS) {
        final info = await _deviceInfo.iosInfo;
        return (info.utsname.machine, '${info.systemName} ${info.systemVersion}', 'ios');
      }
    } catch (_) {
      // Fall through to the generic descriptor on unsupported platforms.
    }
    return ('unknown', 'unknown', defaultTargetPlatform.name);
  }
}

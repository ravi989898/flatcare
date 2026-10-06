import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';

/// The phone settings a visitor request needs to arrive with the app
/// closed. Read and opened through AlertSetup.kt; Android only - on iOS
/// and web every check passes.
class AlertSetupStatus {
  const AlertSetupStatus({
    this.notifications = true,
    this.battery = true,
    this.autoStartNeeded = false,
    this.autoStartDone = true,
    this.fullScreenNeeded = false,
    this.fullScreen = true,
    this.brand = '',
  });

  factory AlertSetupStatus.fromMap(Map<Object?, Object?> map) => AlertSetupStatus(
        notifications: map['notifications'] == true,
        battery: map['battery'] == true,
        autoStartNeeded: map['autoStartNeeded'] == true,
        autoStartDone: map['autoStartDone'] == true,
        fullScreenNeeded: map['fullScreenNeeded'] == true,
        fullScreen: map['fullScreen'] == true,
        brand: '${map['brand'] ?? ''}',
      );

  final bool notifications;
  final bool battery;
  final bool autoStartNeeded;
  final bool autoStartDone;
  final bool fullScreenNeeded;
  final bool fullScreen;
  final String brand;

  /// Everything a closed-app visitor request depends on. (Full-screen is
  /// only the incoming-call style popup - the notification arrives without it.)
  bool get complete => notifications && battery && (!autoStartNeeded || autoStartDone);
}

class AlertSetup {
  static const _channel = MethodChannel('flatcare/alert_setup');

  static bool get supported => !kIsWeb && defaultTargetPlatform == TargetPlatform.android;

  static Future<AlertSetupStatus> status() async {
    if (!supported) return const AlertSetupStatus();

    try {
      final map = await _channel.invokeMethod<Map<Object?, Object?>>('status');
      return map == null ? const AlertSetupStatus() : AlertSetupStatus.fromMap(map);
    } catch (_) {
      return const AlertSetupStatus();
    }
  }

  static Future<void> openNotifications() => _invoke('openNotifications');
  static Future<void> openBattery() => _invoke('openBattery');
  static Future<void> openAutoStart() => _invoke('openAutoStart');
  static Future<void> openFullScreen() => _invoke('openFullScreen');

  /// Whether to open the setup screen by itself after sign-in: only while
  /// something is missing, and at most once a day so it never nags.
  static Future<bool> shouldPrompt() async {
    if (!supported || (await status()).complete) return false;

    try {
      final last = await _channel.invokeMethod<int>('lastPrompted') ?? 0;
      return DateTime.now().millisecondsSinceEpoch - last > const Duration(days: 1).inMilliseconds;
    } catch (_) {
      return false;
    }
  }

  static Future<void> markPrompted() => _invoke('markPrompted');

  static Future<void> _invoke(String method) async {
    try {
      await _channel.invokeMethod<void>(method);
    } catch (_) {
      // That settings screen doesn't exist on this phone.
    }
  }
}

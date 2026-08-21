import 'package:flutter/foundation.dart' show kIsWeb;

/// Build-time configuration. Override with, e.g.:
///   flutter run --dart-define=API_BASE_URL=http://192.168.1.10:8000/api/v1
class AppConfig {
  AppConfig._();

  static const _override = String.fromEnvironment('API_BASE_URL');

  /// No explicit override: on web (`flutter run -d chrome`) the browser can
  /// reach the backend directly at 127.0.0.1, but on Android that address
  /// means the emulator itself, not the host machine — 10.0.2.2 is the
  /// emulator's alias for the host's localhost instead. A physical device
  /// or iOS simulator still needs an explicit --dart-define with the host
  /// machine's LAN IP.
  static String get apiBaseUrl {
    if (_override.isNotEmpty) return _override;
    return kIsWeb ? 'http://127.0.0.1:8000/api/v1' : 'http://10.0.2.2:8000/api/v1';
  }
}

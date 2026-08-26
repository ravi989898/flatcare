import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Persists small local-only app preferences (currently just the theme
/// mode) across restarts. Reuses flutter_secure_storage — already a
/// dependency for the auth token — rather than adding shared_preferences
/// as a second storage mechanism for a single string value.
class PreferencesStorage {
  PreferencesStorage() : _storage = const FlutterSecureStorage();

  final FlutterSecureStorage _storage;

  static const _themeModeKey = 'flatcare_theme_mode';

  Future<void> saveThemeMode(String mode) => _storage.write(key: _themeModeKey, value: mode);

  Future<String?> readThemeMode() => _storage.read(key: _themeModeKey);
}

import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Persists the bearer token issued by POST /api/v1/auth/login across app
/// restarts. Uses the platform keystore/keychain via flutter_secure_storage
/// rather than SharedPreferences, since this is a credential.
class TokenStorage {
  TokenStorage() : _storage = const FlutterSecureStorage();

  final FlutterSecureStorage _storage;

  static const _tokenKey = 'flatcare_api_token';

  // ApiClient reads the token on every request; a Keystore read (platform
  // channel + decryption) each time added latency to every API call, so it
  // is read from storage once per isolate and kept in memory after that.
  static String? _cached;
  static bool _loaded = false;

  Future<void> save(String token) async {
    await _storage.write(key: _tokenKey, value: token);
    _cached = token;
    _loaded = true;
  }

  Future<String?> read() async {
    if (_loaded) return _cached;
    _cached = await _storage.read(key: _tokenKey);
    _loaded = true;
    return _cached;
  }

  Future<void> clear() async {
    await _storage.delete(key: _tokenKey);
    _cached = null;
    _loaded = true;
  }
}

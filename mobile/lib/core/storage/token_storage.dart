import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Persists the bearer token issued by POST /api/v1/auth/login across app
/// restarts. Uses the platform keystore/keychain via flutter_secure_storage
/// rather than SharedPreferences, since this is a credential.
class TokenStorage {
  TokenStorage() : _storage = const FlutterSecureStorage();

  final FlutterSecureStorage _storage;

  static const _tokenKey = 'flatcare_api_token';

  Future<void> save(String token) => _storage.write(key: _tokenKey, value: token);

  Future<String?> read() => _storage.read(key: _tokenKey);

  Future<void> clear() => _storage.delete(key: _tokenKey);
}

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client.dart';
import '../../../core/models/society.dart';
import '../../../core/models/user_profile.dart';
import '../../../core/providers/core_providers.dart';

class LoginResult {
  LoginResult({required this.token, required this.society, required this.user});

  final String token;
  final Society society;
  final UserProfile user;
}

class MeResult {
  MeResult({required this.society, required this.user});

  final Society society;
  final UserProfile user;
}

class AuthRepository {
  AuthRepository(this._client);

  final ApiClient _client;

  Future<LoginResult> login({
    required String email,
    required String password,
    String? deviceId,
    String? devicePlatform,
  }) async {
    final response = await _client.post('/auth/login', data: {
      'email': email,
      'password': password,
      if (deviceId != null) 'device_id': deviceId,
      if (devicePlatform != null) 'device_platform': devicePlatform,
    });

    final data = response['data'] as Map<String, dynamic>;

    return LoginResult(
      token: data['token'] as String,
      society: Society.fromJson(data['society'] as Map<String, dynamic>),
      user: UserProfile.fromJson(data['user'] as Map<String, dynamic>),
    );
  }

  Future<MeResult> me() async {
    final response = await _client.get('/me');
    final data = response['data'] as Map<String, dynamic>;

    return MeResult(
      society: Society.fromJson(data['society'] as Map<String, dynamic>),
      user: UserProfile.fromJson(data['user'] as Map<String, dynamic>),
    );
  }

  Future<void> logout() => _client.post('/auth/logout');

  Future<void> forgotPassword(String email) =>
      _client.post('/auth/forgot-password', data: {'email': email});

  Future<void> resetPassword({
    required String email,
    required String token,
    required String password,
    required String passwordConfirmation,
  }) {
    return _client.post('/auth/reset-password', data: {
      'email': email,
      'token': token,
      'password': password,
      'password_confirmation': passwordConfirmation,
    });
  }
}

final authRepositoryProvider = Provider<AuthRepository>((ref) {
  return AuthRepository(ref.watch(apiClientProvider));
});

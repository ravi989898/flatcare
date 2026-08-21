import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/models/society.dart';
import '../../../core/models/user_profile.dart';
import '../../../core/providers/core_providers.dart';
import '../data/auth_repository.dart';

class AuthState {
  AuthState({required this.society, required this.user});

  final Society society;
  final UserProfile user;
}

/// Session state for the whole app. `null` data means "logged out" — the
/// router's redirect (app_router.dart) watches this directly.
class AuthController extends AsyncNotifier<AuthState?> {
  @override
  Future<AuthState?> build() async {
    // On cold start, a stored token doesn't necessarily mean a *valid*
    // session (it could've expired or been revoked server-side), so
    // restoring one always re-validates against /me rather than trusting
    // the stored user data blindly.
    final token = await ref.read(tokenStorageProvider).read();
    if (token == null) return null;

    try {
      final me = await ref.read(authRepositoryProvider).me();
      return AuthState(society: me.society, user: me.user);
    } catch (_) {
      await ref.read(tokenStorageProvider).clear();
      return null;
    }
  }

  Future<void> login({required String email, required String password}) async {
    state = const AsyncLoading();
    try {
      final result = await ref.read(authRepositoryProvider).login(email: email, password: password);
      await ref.read(tokenStorageProvider).save(result.token);
      state = AsyncData(AuthState(society: result.society, user: result.user));
    } catch (e, stackTrace) {
      state = AsyncError(e, stackTrace);
      rethrow;
    }
  }

  Future<void> logout() async {
    try {
      await ref.read(authRepositoryProvider).logout();
    } catch (_) {
      // Best-effort: even if revoking server-side fails (e.g. offline),
      // still clear the local session below.
    }
    await ref.read(tokenStorageProvider).clear();
    state = const AsyncData(null);
  }

  /// Called by ApiClient.onUnauthorized when any request comes back 401 —
  /// the token is already invalid server-side, so there's nothing to revoke.
  void forceLogout() {
    ref.read(tokenStorageProvider).clear();
    state = const AsyncData(null);
  }

  /// Re-fetches the current user (e.g. after editing the profile) without
  /// disturbing the loading/error state of the rest of the app.
  Future<void> refreshUser() async {
    final me = await ref.read(authRepositoryProvider).me();
    state = AsyncData(AuthState(society: me.society, user: me.user));
  }
}

final authControllerProvider = AsyncNotifierProvider<AuthController, AuthState?>(AuthController.new);

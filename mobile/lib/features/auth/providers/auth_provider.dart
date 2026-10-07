import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_exception.dart';
import '../../../core/models/society.dart';
import '../../../core/models/user_profile.dart';
import '../../../core/providers/core_providers.dart';
import '../../../core/push/push_service.dart';
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

    // Only a rejected token ends the session. A phone woken from the lock
    // screen by a visitor request often has no network for a moment;
    // treating that as "logged out" cleared the token, so the gate-approval
    // screen never opened. Retry instead, and keep the token if it still
    // fails so the next launch can restore the session.
    for (var attempt = 1;; attempt++) {
      try {
        final me = await ref.read(authRepositoryProvider).me();
        return AuthState(society: me.society, user: me.user);
      } on ApiException catch (e) {
        if (e.statusCode == 401 || e.statusCode == 403) {
          await ref.read(tokenStorageProvider).clear();
          return null;
        }
      } catch (_) {
        // Unexpected response - retried like a network error.
      }
      if (attempt == 3) return null;
      await Future<void>.delayed(Duration(seconds: attempt * 2));
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

  Future<void> loginWithOtp({required String mobileNumber, required String otp}) async {
    state = const AsyncLoading();
    try {
      final result = await ref.read(authRepositoryProvider).verifyOtp(mobileNumber: mobileNumber, otp: otp);
      await ref.read(tokenStorageProvider).save(result.token);
      state = AsyncData(AuthState(society: result.society, user: result.user));
    } catch (e, stackTrace) {
      state = AsyncError(e, stackTrace);
      rethrow;
    }
  }

  Future<void> logout() async {
    // Stop pushes to this device first, while the token is still valid.
    await ref.read(pushServiceProvider).unregisterDevice();
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

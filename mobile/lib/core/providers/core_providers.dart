import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../api/api_client.dart';
import '../storage/token_storage.dart';
import '../../features/auth/providers/auth_provider.dart';

final tokenStorageProvider = Provider<TokenStorage>((ref) => TokenStorage());

/// A 401 from any endpoint clears the local session so go_router's redirect
/// (see app_router.dart) sends the user back to /login. `ref.read` here
/// (not `ref.watch`) is deliberate: this closure only runs later, in
/// response to a real 401, so it can't create an eager circular build even
/// though authControllerProvider's repository depends on this same client.
final apiClientProvider = Provider<ApiClient>((ref) {
  final storage = ref.watch(tokenStorageProvider);

  return ApiClient(
    storage,
    onUnauthorized: () => ref.read(authControllerProvider.notifier).forceLogout(),
  );
});

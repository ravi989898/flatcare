import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client.dart';
import '../../../core/providers/core_providers.dart';

/// Which of the app's own hardcoded home-screen menu tiles (see
/// home_screen.dart's `_MenuItem.key`) the signed-in user's role is allowed
/// to see — set per role by their society's Admin under the web portal's
/// Settings -> Permissions -> App Permission.
class AppMenuRepository {
  AppMenuRepository(this._client);

  final ApiClient _client;

  Future<Set<String>> visibleKeys() async {
    final response = await _client.get('/app-menu-items');
    final keys = (response['data'] as Map<String, dynamic>)['keys'] as List;
    return keys.cast<String>().toSet();
  }
}

final appMenuRepositoryProvider = Provider<AppMenuRepository>((ref) {
  return AppMenuRepository(ref.watch(apiClientProvider));
});

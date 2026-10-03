import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client.dart';
import '../../../core/providers/core_providers.dart';

/// Which of the app's own home-screen menu tiles (see home_screen.dart's
/// `_MenuItem.key`) the signed-in user may see — set per role by their
/// society's Admin under the web portal's Settings -> Permissions -> App
/// Permission.
class AppMenuAccess {
  const AppMenuAccess({required this.visible, required this.managed});

  /// Tiles this user's roles are allowed to see.
  final Set<String> visible;

  /// Every tile App Permission controls. Tiles outside this set aren't
  /// configurable and always show.
  final Set<String> managed;

  /// For ordinary tiles: hidden only when App Permission manages the tile
  /// and hasn't granted it.
  bool allows(String key) => !managed.contains(key) || visible.contains(key);

  /// For Society Admin features (Water Readings, Payment Status): shown only
  /// when explicitly granted.
  bool grants(String key) => visible.contains(key);
}

class AppMenuRepository {
  AppMenuRepository(this._client);

  final ApiClient _client;

  Future<AppMenuAccess> access() async {
    final response = await _client.get('/app-menu-items');
    final data = response['data'] as Map<String, dynamic>;
    final visible = (data['keys'] as List).cast<String>().toSet();
    // Older servers only sent 'keys' - then treat just those as managed, so
    // no ordinary tile gets hidden by mistake.
    final managed = (data['managed'] as List?)?.cast<String>().toSet() ?? visible;
    return AppMenuAccess(visible: visible, managed: managed);
  }
}

final appMenuRepositoryProvider = Provider<AppMenuRepository>((ref) {
  return AppMenuRepository(ref.watch(apiClientProvider));
});

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../auth/providers/auth_provider.dart';
import '../data/app_menu_repository.dart';

/// Fetched once per signed-in user (re-fetched when a different user signs
/// in) and kept in memory while the home screen can be revisited.
/// HomeScreen shows ordinary tiles while this is loading or failed, but
/// keeps Society Admin features hidden until they are actually granted —
/// the server refuses those requests anyway (app.menu middleware).
final appMenuAccessProvider = FutureProvider<AppMenuAccess?>((ref) {
  final userId = ref.watch(authControllerProvider.select((auth) => auth.valueOrNull?.user.id));
  if (userId == null) return null;
  return ref.watch(appMenuRepositoryProvider).access();
});

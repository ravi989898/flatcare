import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../data/app_menu_repository.dart';

/// Fetched once per session (not `.autoDispose` — same lifetime as
/// authControllerProvider) and kept in memory for as long as the home
/// screen can be revisited. HomeScreen treats a still-loading or failed
/// fetch as "show everything": this is a per-society display preference,
/// not an access control boundary, so failing open beats hiding every
/// tile behind a slow or broken request.
final appMenuVisibleKeysProvider = FutureProvider<Set<String>>((ref) {
  return ref.watch(appMenuRepositoryProvider).visibleKeys();
});

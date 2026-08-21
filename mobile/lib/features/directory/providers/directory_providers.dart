import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../data/directory_entry.dart';
import '../data/directory_repository.dart';

final directorySearchProvider = StateProvider.autoDispose<String>((ref) => '');

final directoryListProvider = FutureProvider.autoDispose<List<DirectoryEntry>>((ref) {
  final search = ref.watch(directorySearchProvider);
  return ref.watch(directoryRepositoryProvider).list(search: search);
});

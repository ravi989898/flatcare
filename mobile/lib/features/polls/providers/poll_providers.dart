import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../data/poll.dart';
import '../data/poll_repository.dart';

final pollListProvider = FutureProvider.autoDispose<List<Poll>>((ref) {
  return ref.watch(pollRepositoryProvider).list();
});

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../data/event.dart';
import '../data/event_repository.dart';

final eventListProvider = FutureProvider.autoDispose.family<List<Event>, String>((ref, when) {
  return ref.watch(eventRepositoryProvider).list(when: when);
});

final eventDetailProvider = FutureProvider.autoDispose.family<Event, int>((ref, id) {
  return ref.watch(eventRepositoryProvider).show(id);
});

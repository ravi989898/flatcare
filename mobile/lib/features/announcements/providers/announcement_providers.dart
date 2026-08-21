import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../data/announcement.dart';
import '../data/announcement_repository.dart';

final announcementListProvider = FutureProvider.autoDispose<List<Announcement>>((ref) {
  return ref.watch(announcementRepositoryProvider).list();
});

final announcementDetailProvider = FutureProvider.autoDispose.family<Announcement, int>((ref, id) {
  return ref.watch(announcementRepositoryProvider).show(id);
});

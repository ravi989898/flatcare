import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../data/committee_member.dart';
import '../data/committee_repository.dart';

final committeeMemberListProvider = FutureProvider.autoDispose<List<CommitteeMember>>((ref) {
  return ref.watch(committeeRepositoryProvider).list();
});

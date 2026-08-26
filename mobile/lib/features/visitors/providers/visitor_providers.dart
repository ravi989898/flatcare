import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../data/visitor.dart';
import '../data/visitor_repository.dart';

final visitorListProvider = FutureProvider.autoDispose.family<List<Visitor>, String?>((ref, status) {
  return ref.watch(visitorRepositoryProvider).list(status: status);
});

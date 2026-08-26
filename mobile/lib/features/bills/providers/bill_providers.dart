import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../data/bill.dart';
import '../data/bill_repository.dart';

final billListProvider = FutureProvider.autoDispose.family<List<Bill>, String?>((ref, status) {
  return ref.watch(billRepositoryProvider).list(status: status);
});

final billDetailProvider = FutureProvider.autoDispose.family<Bill, int>((ref, id) {
  return ref.watch(billRepositoryProvider).show(id);
});

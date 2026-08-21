import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../data/complaint.dart';
import '../data/complaint_repository.dart';

final complaintListProvider = FutureProvider.autoDispose.family<List<Complaint>, String?>((ref, status) {
  return ref.watch(complaintRepositoryProvider).list(status: status);
});

final complaintDetailProvider = FutureProvider.autoDispose.family<Complaint, int>((ref, id) {
  return ref.watch(complaintRepositoryProvider).show(id);
});

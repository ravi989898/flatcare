import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../data/maintenance_request.dart';
import '../data/maintenance_request_repository.dart';

final maintenanceRequestListProvider =
    FutureProvider.autoDispose.family<List<MaintenanceRequest>, String?>((ref, status) {
  return ref.watch(maintenanceRequestRepositoryProvider).list(status: status);
});

final maintenanceRequestDetailProvider =
    FutureProvider.autoDispose.family<MaintenanceRequest, int>((ref, id) {
  return ref.watch(maintenanceRequestRepositoryProvider).show(id);
});

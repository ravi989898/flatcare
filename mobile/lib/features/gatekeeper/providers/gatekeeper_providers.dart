import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/models/flat.dart';
import '../../visitors/data/visitor.dart';
import '../data/duty_repository.dart';
import '../data/duty_status.dart';
import '../data/guard_flat_repository.dart';
import '../data/guard_vehicle.dart';
import '../data/guard_vehicle_repository.dart';
import '../data/guard_visitor_repository.dart';

/// 'active' (pending + approved + inside) by default — same convention as
/// the backend's Api\V1\Guard\VisitorController::index — set by the tab bar
/// on GatekeeperVisitorListScreen.
final guardVisitorStatusProvider = StateProvider.autoDispose<String>((ref) => 'active');
final guardVisitorSearchProvider = StateProvider.autoDispose<String>((ref) => '');

final guardVisitorListProvider = FutureProvider.autoDispose<List<Visitor>>((ref) {
  final status = ref.watch(guardVisitorStatusProvider);
  final search = ref.watch(guardVisitorSearchProvider);
  return ref.watch(guardVisitorRepositoryProvider).list(status: status, search: search);
});

final dutyStatusProvider = FutureProvider.autoDispose<DutyStatus>((ref) {
  return ref.watch(dutyRepositoryProvider).status();
});

/// Backs the full-screen flat-picker sheet on the walk-in check-in form
/// (_FlatPickerSheet in gatekeeper_visitor_checkin_screen.dart).
final guardFlatSearchProvider = StateProvider.autoDispose<String>((ref) => '');

final guardFlatListProvider = FutureProvider.autoDispose<List<Flat>>((ref) {
  return ref.watch(guardFlatRepositoryProvider).search(ref.watch(guardFlatSearchProvider));
});

final guardVehicleSearchProvider = StateProvider.autoDispose<String>((ref) => '');

final guardVehicleListProvider = FutureProvider.autoDispose<List<GuardVehicle>>((ref) {
  return ref.watch(guardVehicleRepositoryProvider).search(ref.watch(guardVehicleSearchProvider));
});

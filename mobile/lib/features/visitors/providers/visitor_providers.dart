import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../data/daily_helper.dart';
import '../data/daily_helper_repository.dart';
import '../data/gate_keeper_repository.dart';
import '../data/visitor.dart';
import '../data/visitor_repository.dart';
import '../data/visitor_settings.dart';
import '../data/visitor_settings_repository.dart';

final visitorListProvider = FutureProvider.autoDispose.family<List<Visitor>, VisitorQuery>((ref, query) {
  return ref.watch(visitorRepositoryProvider).list(query);
});

/// Only the quick Pre-Approvals that haven't been used yet — the "Pre-Approved
/// Entry" screen.
final preApprovedListProvider = FutureProvider.autoDispose<List<Visitor>>((ref) {
  return ref.watch(visitorRepositoryProvider).list((status: 'pending', search: null, from: null, to: null, kind: 'pre_approval'));
});

final dailyHelperListProvider = FutureProvider.autoDispose<List<DailyHelper>>((ref) {
  return ref.watch(dailyHelperRepositoryProvider).list();
});

final gateKeepersProvider = FutureProvider.autoDispose<GateKeepers>((ref) {
  return ref.watch(gateKeeperRepositoryProvider).fetch();
});

final visitorSettingsProvider = FutureProvider.autoDispose<VisitorSettings>((ref) {
  return ref.watch(visitorSettingsRepositoryProvider).fetch();
});

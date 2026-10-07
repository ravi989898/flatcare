import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/models/flat.dart';
import '../../visitors/data/visitor.dart';
import '../data/duty_repository.dart';
import '../data/duty_status.dart';
import '../data/guard_flat_repository.dart';
import '../data/guard_summary.dart';
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

/// Every flat, for the walk-in form's block -> flat picker.
final guardAllFlatsProvider = FutureProvider.autoDispose<List<Flat>>((ref) {
  return ref.watch(guardFlatRepositoryProvider).all();
});

final guardVehicleSearchProvider = StateProvider.autoDispose<String>((ref) => '');

final guardVehicleListProvider = FutureProvider.autoDispose<List<GuardVehicle>>((ref) {
  return ref.watch(guardVehicleRepositoryProvider).search(ref.watch(guardVehicleSearchProvider));
});

String _ymd(DateTime d) => DateFormat('yyyy-MM-dd').format(d);

/// Counts on the Gate Duty tiles.
final guardSummaryProvider = FutureProvider.autoDispose<GuardSummary>((ref) {
  return ref.watch(guardVisitorRepositoryProvider).summary();
});

/// Today's walk-in requests still waiting for the resident to respond.
final guardPendingRequestsProvider = FutureProvider.autoDispose<List<Visitor>>((ref) async {
  final page = await ref.watch(guardVisitorRepositoryProvider).page(kind: 'requests', date: _ymd(DateTime.now()));
  return page.items;
});

final gatePassSearchProvider = StateProvider.autoDispose<String>((ref) => '');

/// Residents' gate passes / pre-approvals the gate can still honour.
final gatePassListProvider = FutureProvider.autoDispose<List<Visitor>>((ref) async {
  final search = ref.watch(gatePassSearchProvider);
  final page = await ref.watch(guardVisitorRepositoryProvider).page(kind: 'passes', search: search);
  return page.items;
});

final closedHouseSearchProvider = StateProvider.autoDispose<String>((ref) => '');

final closedHouseListProvider = FutureProvider.autoDispose<List<Flat>>((ref) {
  return ref.watch(guardFlatRepositoryProvider).closedHouses(search: ref.watch(closedHouseSearchProvider));
});

/// Visitor Log filters: a day (null = every day), a status ('all' or one
/// status) and free-text search.
class VisitorLogFilter {
  const VisitorLogFilter({this.date, this.status = 'all', this.search = ''});

  final DateTime? date;
  final String status;
  final String search;

  VisitorLogFilter copyWith({DateTime? date, bool clearDate = false, String? status, String? search}) => VisitorLogFilter(
        date: clearDate ? null : (date ?? this.date),
        status: status ?? this.status,
        search: search ?? this.search,
      );
}

final visitorLogFilterProvider = StateProvider.autoDispose<VisitorLogFilter>((ref) => VisitorLogFilter(date: DateTime.now()));

class VisitorLogState {
  const VisitorLogState({required this.items, required this.page, required this.hasMore, this.total, this.loadingMore = false});

  final List<Visitor> items;
  final int page;
  final bool hasMore;
  final int? total;
  final bool loadingMore;

  VisitorLogState copyWith({List<Visitor>? items, int? page, bool? hasMore, bool? loadingMore}) => VisitorLogState(
        items: items ?? this.items,
        page: page ?? this.page,
        hasMore: hasMore ?? this.hasMore,
        total: total,
        loadingMore: loadingMore ?? this.loadingMore,
      );
}

/// Every visitor, newest first, a page at a time for the current filter.
class VisitorLogController extends AutoDisposeAsyncNotifier<VisitorLogState> {
  Future<GuardVisitorPage> _fetch(int page) {
    final filter = ref.read(visitorLogFilterProvider);
    return ref.read(guardVisitorRepositoryProvider).page(
          status: filter.status,
          date: filter.date == null ? null : _ymd(filter.date!),
          search: filter.search,
          page: page,
        );
  }

  @override
  Future<VisitorLogState> build() async {
    ref.watch(visitorLogFilterProvider);
    final first = await _fetch(1);
    return VisitorLogState(items: first.items, page: first.page, hasMore: first.hasMore, total: first.total);
  }

  Future<void> loadMore() async {
    final current = state.valueOrNull;
    if (current == null || !current.hasMore || current.loadingMore) return;

    state = AsyncData(current.copyWith(loadingMore: true));
    try {
      final next = await _fetch(current.page + 1);
      state = AsyncData(current.copyWith(
        items: [...current.items, ...next.items],
        page: next.page,
        hasMore: next.hasMore,
        loadingMore: false,
      ));
    } catch (_) {
      state = AsyncData(current.copyWith(loadingMore: false));
    }
  }
}

final visitorLogProvider = AsyncNotifierProvider.autoDispose<VisitorLogController, VisitorLogState>(VisitorLogController.new);

/// Everything a gate action (entry/exit) or a push can change. Pass
/// `ref.invalidate` from a widget or a provider.
void invalidateGateLists(void Function(ProviderOrFamily provider) invalidate) {
  for (final provider in <ProviderOrFamily>[
    guardVisitorListProvider,
    guardSummaryProvider,
    guardPendingRequestsProvider,
    gatePassListProvider,
    visitorLogProvider,
  ]) {
    invalidate(provider);
  }
}

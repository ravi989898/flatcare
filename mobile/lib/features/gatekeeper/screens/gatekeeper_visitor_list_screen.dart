import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../../core/widgets/fc/fc.dart';
import '../providers/gatekeeper_providers.dart';
import '../widgets/gate_visitor_card.dart';

const _statusFilters = [
  (label: 'All', status: 'all', color: AppColors.primary, icon: Icons.list_alt_rounded),
  (label: 'Inside', status: 'checked_in', color: AppColors.accentTeal, icon: Icons.login_rounded),
  (label: 'Approved', status: 'approved', color: AppColors.success, icon: Icons.verified_rounded),
  (label: 'Waiting', status: 'pending', color: AppColors.warning, icon: Icons.hourglass_top_rounded),
  (label: 'Exited', status: 'checked_out', color: AppColors.accentSlate, icon: Icons.logout_rounded),
  (label: 'Rejected', status: 'denied', color: AppColors.danger, icon: Icons.block_rounded),
];

/// Visitor Log — every visitor who came to the gate, newest first, with a
/// date filter (defaults to today; step day by day, pick any date from the
/// calendar, or show every day), status chips and search. Rows keep their
/// gate actions (Allow Entry / Mark Exit).
class GatekeeperVisitorListScreen extends ConsumerStatefulWidget {
  const GatekeeperVisitorListScreen({super.key, this.initialStatus});

  /// Preselects a status chip when opened from a shortcut or notification.
  final String? initialStatus;

  @override
  ConsumerState<GatekeeperVisitorListScreen> createState() => _GatekeeperVisitorListScreenState();
}

class _GatekeeperVisitorListScreenState extends ConsumerState<GatekeeperVisitorListScreen> {
  final _scroll = ScrollController();
  Timer? _debounce;

  @override
  void initState() {
    super.initState();
    _scroll.addListener(() {
      if (_scroll.position.extentAfter < 400) ref.read(visitorLogProvider.notifier).loadMore();
    });
    final initial = widget.initialStatus;
    if (initial != null && _statusFilters.any((f) => f.status == initial)) {
      WidgetsBinding.instance.addPostFrameCallback((_) => _update((f) => f.copyWith(status: initial)));
    }
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _scroll.dispose();
    super.dispose();
  }

  void _update(VisitorLogFilter Function(VisitorLogFilter f) change) {
    final notifier = ref.read(visitorLogFilterProvider.notifier);
    notifier.state = change(notifier.state);
  }

  Future<void> _pickDate(DateTime? current) async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: current ?? now,
      firstDate: DateTime(now.year - 3),
      lastDate: now,
      helpText: 'Show visitors on',
    );
    if (picked != null) _update((f) => f.copyWith(date: picked));
  }

  @override
  Widget build(BuildContext context) {
    final filter = ref.watch(visitorLogFilterProvider);
    final log = ref.watch(visitorLogProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Visitor Log')),
      floatingActionButton: FcFab(
        label: 'Walk-in',
        icon: Icons.person_add_alt_1_rounded,
        onPressed: () => context.push('/gatekeeper/visitors/check-in'),
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 10),
            child: _DateBar(
              date: filter.date,
              onPrev: () => _update((f) => f.copyWith(date: (f.date ?? DateTime.now()).subtract(const Duration(days: 1)))),
              onNext: () => _update((f) => f.copyWith(date: f.date!.add(const Duration(days: 1)))),
              onPick: () => _pickDate(filter.date),
              onToggleAll: () => _update((f) => f.date == null ? f.copyWith(date: DateTime.now()) : f.copyWith(clearDate: true)),
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 10),
            child: TextField(
              textInputAction: TextInputAction.search,
              decoration: const InputDecoration(
                hintText: 'Name, phone, vehicle, flat or pass code',
                prefixIcon: Icon(Icons.search_rounded),
              ),
              onChanged: (value) {
                _debounce?.cancel();
                _debounce = Timer(const Duration(milliseconds: 350), () => _update((f) => f.copyWith(search: value.trim())));
              },
            ),
          ),
          SizedBox(
            height: 40,
            child: ListView.separated(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 16),
              itemCount: _statusFilters.length,
              separatorBuilder: (_, _) => const SizedBox(width: 8),
              itemBuilder: (context, i) {
                final f = _statusFilters[i];
                final selected = filter.status == f.status;
                return ChoiceChip(
                  selected: selected,
                  showCheckmark: false,
                  avatar: Icon(f.icon, size: 17, color: selected ? Colors.white : f.color),
                  label: Text(f.label),
                  labelStyle: TextStyle(fontWeight: FontWeight.w700, color: selected ? Colors.white : f.color),
                  selectedColor: f.color,
                  backgroundColor: AppColors.soft(f.color),
                  side: BorderSide.none,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(999)),
                  onSelected: (_) => _update((x) => x.copyWith(status: f.status)),
                );
              },
            ),
          ),
          const SizedBox(height: 8),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => ref.refresh(visitorLogProvider.future),
              child: AsyncView<VisitorLogState>(
                value: log,
                skeleton: true,
                onRetry: () => ref.invalidate(visitorLogProvider),
                builder: (context, state) {
                  if (state.items.isEmpty) {
                    return EmptyState(
                      icon: Icons.event_busy_rounded,
                      title: 'No visitors',
                      message: filter.date == null
                          ? 'No visitor matches these filters.'
                          : 'No visitor came on ${DateFormat('d MMM yyyy').format(filter.date!)} for these filters.',
                    );
                  }

                  return ListView.separated(
                    controller: _scroll,
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.fromLTRB(16, 4, 16, 100),
                    itemCount: state.items.length + 1 + (state.hasMore ? 1 : 0),
                    separatorBuilder: (_, _) => const SizedBox(height: 12),
                    itemBuilder: (context, index) {
                      if (index == 0) {
                        final total = state.total ?? state.items.length;
                        return Padding(
                          padding: const EdgeInsets.only(left: 4),
                          child: Text(
                            '$total ${total == 1 ? 'visitor' : 'visitors'} · newest first',
                            style: const TextStyle(fontWeight: FontWeight.w700, color: AppColors.textSecondary),
                          ),
                        );
                      }
                      if (index == state.items.length + 1) {
                        return const Padding(
                          padding: EdgeInsets.all(16),
                          child: Center(child: CircularProgressIndicator()),
                        );
                      }
                      return GateVisitorCard(visitor: state.items[index - 1]);
                    },
                  );
                },
              ),
            ),
          ),
        ],
      ),
    );
  }
}

/// "‹  Today, 23 Sep  ›  📅  All days" — big tap targets for stepping days.
class _DateBar extends StatelessWidget {
  const _DateBar({required this.date, required this.onPrev, required this.onNext, required this.onPick, required this.onToggleAll});

  final DateTime? date;
  final VoidCallback onPrev;
  final VoidCallback onNext;
  final VoidCallback onPick;
  final VoidCallback onToggleAll;

  String _label(DateTime d) {
    final today = DateUtils.dateOnly(DateTime.now());
    final day = DateUtils.dateOnly(d);
    final diff = today.difference(day).inDays;
    final pretty = DateFormat('d MMM yyyy').format(d);
    if (diff == 0) return 'Today · $pretty';
    if (diff == 1) return 'Yesterday · $pretty';
    return DateFormat('EEE, d MMM yyyy').format(d);
  }

  @override
  Widget build(BuildContext context) {
    final isToday = date != null && DateUtils.isSameDay(date, DateTime.now());

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 4),
      decoration: BoxDecoration(gradient: AppColors.primaryGradient, borderRadius: BorderRadius.circular(16)),
      child: Row(
        children: [
          IconButton(
            onPressed: onPrev,
            tooltip: 'Previous day',
            icon: const Icon(Icons.chevron_left_rounded, color: Colors.white, size: 28),
          ),
          Expanded(
            child: InkWell(
              borderRadius: BorderRadius.circular(12),
              onTap: onPick,
              child: Padding(
                padding: const EdgeInsets.symmetric(vertical: 10),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(Icons.calendar_month_rounded, color: Colors.white, size: 20),
                    const SizedBox(width: 8),
                    Flexible(
                      child: Text(
                        date == null ? 'All days' : _label(date!),
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 15),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
          IconButton(
            onPressed: date == null || isToday ? null : onNext,
            tooltip: 'Next day',
            icon: Icon(Icons.chevron_right_rounded, color: date == null || isToday ? Colors.white38 : Colors.white, size: 28),
          ),
          TextButton(
            onPressed: onToggleAll,
            style: TextButton.styleFrom(
              foregroundColor: AppColors.primary,
              backgroundColor: Colors.white,
              minimumSize: const Size(0, 38),
              padding: const EdgeInsets.symmetric(horizontal: 12),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            child: Text(date == null ? 'Today' : 'All', style: const TextStyle(fontWeight: FontWeight.w800)),
          ),
          const SizedBox(width: 4),
        ],
      ),
    );
  }
}

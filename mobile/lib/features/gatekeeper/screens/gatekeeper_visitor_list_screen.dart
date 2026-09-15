import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/api/api_exception.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/formatters.dart';
import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/photo_avatar.dart';
import '../../../core/widgets/status_chip.dart';
import '../../visitors/data/visitor.dart';
import '../data/guard_visitor_repository.dart';
import '../providers/gatekeeper_providers.dart';
import '../widgets/purpose_style.dart';

const _tabs = [
  (label: 'Currently In', status: 'checked_in'),
  (label: 'Expected', status: 'pending'),
  (label: 'All', status: 'all'),
];

/// The gate register — currently-in / expected (resident pre-invited) /
/// full log, each check-in-able or check-out-able right from the row. The
/// resident-facing equivalent (features/visitors/screens/visitor_list_screen.dart)
/// is read-only and scoped to their own flat; this one is society-wide and
/// actionable, matching Society\VisitorController's web gate register.
class GatekeeperVisitorListScreen extends ConsumerStatefulWidget {
  const GatekeeperVisitorListScreen({super.key, this.initialStatus});

  /// Preselects a tab when opened from a home-screen shortcut (e.g.
  /// "Expected Visitors" opens straight to the pending tab).
  final String? initialStatus;

  @override
  ConsumerState<GatekeeperVisitorListScreen> createState() => _GatekeeperVisitorListScreenState();
}

class _GatekeeperVisitorListScreenState extends ConsumerState<GatekeeperVisitorListScreen>
    with SingleTickerProviderStateMixin {
  late final TabController _tabController;

  @override
  void initState() {
    super.initState();
    final initialIndex = _tabs.indexWhere((t) => t.status == widget.initialStatus);
    _tabController = TabController(length: _tabs.length, vsync: this, initialIndex: initialIndex >= 0 ? initialIndex : 0);
    _tabController.addListener(() {
      if (!_tabController.indexIsChanging) return;
      ref.read(guardVisitorStatusProvider.notifier).state = _tabs[_tabController.index].status;
    });

    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(guardVisitorStatusProvider.notifier).state = _tabs[_tabController.index].status;
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final visitors = ref.watch(guardVisitorListProvider);

    return Scaffold(
      backgroundColor: const Color(0xFFF2F3F7),
      appBar: AppBar(
        title: const Text('Visitor Log'),
        foregroundColor: Colors.white,
        flexibleSpace: const DecoratedBox(decoration: BoxDecoration(gradient: AppTheme.brandGradient)),
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.white,
          indicatorWeight: 3,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: [for (final tab in _tabs) Tab(text: tab.label)],
        ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => context.push('/gatekeeper/visitors/check-in'),
        backgroundColor: const Color(0xFF2AB98A),
        icon: const Icon(Icons.add),
        label: const Text('Walk-in'),
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: TextField(
              decoration: const InputDecoration(
                labelText: 'Search by name, phone or vehicle number',
                prefixIcon: Icon(Icons.search),
              ),
              onChanged: (value) => ref.read(guardVisitorSearchProvider.notifier).state = value,
            ),
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => ref.refresh(guardVisitorListProvider.future),
              child: AsyncView<List<Visitor>>(
                value: visitors,
                onRetry: () => ref.invalidate(guardVisitorListProvider),
                builder: (context, items) {
                  if (items.isEmpty) {
                    return const EmptyState(message: 'No visitor entries found.', icon: Icons.people_outline);
                  }

                  return ListView.separated(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.fromLTRB(16, 0, 16, 88),
                    itemCount: items.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 8),
                    itemBuilder: (context, index) => _VisitorTile(visitor: items[index]),
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

class _VisitorTile extends ConsumerStatefulWidget {
  const _VisitorTile({required this.visitor});

  final Visitor visitor;

  @override
  ConsumerState<_VisitorTile> createState() => _VisitorTileState();
}

class _VisitorTileState extends ConsumerState<_VisitorTile> {
  bool _isUpdating = false;

  Future<void> _act(Future<void> Function() action) async {
    setState(() => _isUpdating = true);
    try {
      await action();
      ref.invalidate(guardVisitorListProvider);
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _isUpdating = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final visitor = widget.visitor;
    final timeLabel = visitor.isCheckedIn
        ? 'In: ${formatDateTime(visitor.checkInAt)}'
        : visitor.isPending
            ? 'Pre-approved'
            : 'Out: ${formatDateTime(visitor.checkOutAt)}';

    final purposeStyle = PurposeStyle.of(visitor.purpose);

    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14), side: BorderSide(color: Colors.grey.shade200)),
      child: ListTile(
        leading: visitor.photoUrl != null
            ? PhotoAvatar(url: visitor.photoUrl, name: visitor.visitorName)
            : CircleAvatar(
                backgroundColor: purposeStyle.color.withValues(alpha: 0.15),
                child: Text(purposeStyle.emoji, style: const TextStyle(fontSize: 17)),
              ),
        title: Text(visitor.visitorName, style: const TextStyle(fontWeight: FontWeight.w700)),
        subtitle: Padding(
          padding: const EdgeInsets.only(top: 4),
          child: Text(
            '${visitor.flat?.displayLabel ?? ''} · ${visitor.purpose.replaceAll('_', ' ')}\n$timeLabel',
          ),
        ),
        isThreeLine: true,
        trailing: _isUpdating
            ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2))
            : _ActionButton(visitor: visitor, onAct: _act),
      ),
    );
  }
}

class _ActionButton extends ConsumerWidget {
  const _ActionButton({required this.visitor, required this.onAct});

  final Visitor visitor;
  final Future<void> Function(Future<void> Function() action) onAct;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    if (visitor.isPending) {
      return FilledButton(
        style: FilledButton.styleFrom(backgroundColor: const Color(0xFF2AB930)),
        onPressed: () => onAct(() => ref.read(guardVisitorRepositoryProvider).checkIn(visitor.id)),
        child: const Text('Check In'),
      );
    }

    if (visitor.isCheckedIn) {
      return OutlinedButton(
        style: OutlinedButton.styleFrom(foregroundColor: const Color(0xFFE0245E), side: const BorderSide(color: Color(0xFFE0245E))),
        onPressed: () => onAct(() => ref.read(guardVisitorRepositoryProvider).checkOut(visitor.id)),
        child: const Text('Check Out'),
      );
    }

    return StatusChip(label: visitor.status);
  }
}

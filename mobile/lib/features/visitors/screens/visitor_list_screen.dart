import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:share_plus/share_plus.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/api/api_exception.dart';
import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/photo_avatar.dart';
import '../data/visitor.dart';
import '../data/visitor_repository.dart';
import '../providers/visitor_providers.dart';
import '../widgets/visitor_ui.dart';

/// "My Visitors" — search + date range + status filter over the resident's
/// visitor cards, each with IN/OUT status and quick actions.
class VisitorListScreen extends ConsumerStatefulWidget {
  const VisitorListScreen({super.key});

  @override
  ConsumerState<VisitorListScreen> createState() => _VisitorListScreenState();
}

class _VisitorListScreenState extends ConsumerState<VisitorListScreen> {
  final _searchController = TextEditingController();
  Timer? _debounce;

  late DateTime _from = _today().subtract(const Duration(days: 30));
  late DateTime _to = _today().add(const Duration(days: 30));
  String? _status;
  String _search = '';

  static DateTime _today() {
    final now = DateTime.now();
    return DateTime(now.year, now.month, now.day);
  }

  VisitorQuery get _query => (status: _status, search: _search, from: _from, to: _to, kind: null);

  @override
  void dispose() {
    _debounce?.cancel();
    _searchController.dispose();
    super.dispose();
  }

  void _onSearchChanged(String value) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 400), () {
      if (mounted) setState(() => _search = value);
    });
  }

  Future<void> _pickRange() async {
    final picked = await showDateRangePicker(
      context: context,
      firstDate: DateTime(2020),
      lastDate: _today().add(const Duration(days: 365)),
      initialDateRange: DateTimeRange(start: _from, end: _to),
      builder: (context, child) => Theme(
        data: Theme.of(context).copyWith(colorScheme: Theme.of(context).colorScheme.copyWith(primary: VisitorColors.primary)),
        child: child!,
      ),
    );
    if (picked != null) {
      setState(() {
        _from = picked.start;
        _to = picked.end;
      });
    }
  }

  Future<void> _pickStatus() async {
    const options = <(String?, String, IconData)>[
      (null, 'All visitors', Icons.people_alt_rounded),
      ('checked_in', 'Inside now (IN)', Icons.login_rounded),
      ('checked_out', 'Left (OUT)', Icons.logout_rounded),
      ('pending', 'Expected / awaiting approval', Icons.schedule_rounded),
      ('denied', 'Denied', Icons.block_rounded),
    ];

    final picked = await showModalBottomSheet<(String?,)>(
      context: context,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (context) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Padding(
              padding: EdgeInsets.fromLTRB(20, 20, 20, 8),
              child: Align(
                alignment: Alignment.centerLeft,
                child: Text('Filter by status', style: TextStyle(fontSize: 17, fontWeight: FontWeight.w800)),
              ),
            ),
            for (final (value, label, icon) in options)
              ListTile(
                leading: Icon(icon, color: VisitorColors.primary),
                title: Text(label),
                trailing: value == _status ? const Icon(Icons.check_circle_rounded, color: VisitorColors.primary) : null,
                onTap: () => Navigator.pop(context, (value,)),
              ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );
    if (picked != null) setState(() => _status = picked.$1);
  }

  @override
  Widget build(BuildContext context) {
    final query = _query;
    final visitors = ref.watch(visitorListProvider(query));

    return VisitorScaffold(
      title: 'My Visitors',
      fab: VisitorFab(tooltip: 'New Gate Pass', onPressed: () => context.push('/visitors/invite')),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _searchController,
                    onChanged: _onSearchChanged,
                    textInputAction: TextInputAction.search,
                    decoration: InputDecoration(
                      hintText: 'Search visitors by name, flat, purpose...',
                      hintStyle: const TextStyle(fontSize: 13.5, color: Color(0xFF9CA3AF)),
                      prefixIcon: const Icon(Icons.search_rounded, color: VisitorColors.muted),
                      filled: true,
                      fillColor: Colors.white,
                      contentPadding: const EdgeInsets.symmetric(vertical: 12),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: VisitorColors.border)),
                      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: VisitorColors.border)),
                      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: VisitorColors.primary)),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                Badge(
                  isLabelVisible: _status != null,
                  smallSize: 10,
                  backgroundColor: VisitorColors.gold,
                  child: IconButton.filledTonal(
                    onPressed: _pickStatus,
                    tooltip: 'Filter by status',
                    style: IconButton.styleFrom(backgroundColor: VisitorColors.primarySoft, foregroundColor: VisitorColors.primary),
                    icon: const Icon(Icons.filter_alt_rounded),
                  ),
                ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
            child: InkWell(
              borderRadius: BorderRadius.circular(14),
              onTap: _pickRange,
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: VisitorColors.border),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.calendar_month_rounded, color: VisitorColors.primary, size: 20),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        '${formatVisitorDay(_from)} - ${formatVisitorDay(_to)}',
                        style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13.5),
                      ),
                    ),
                    const Icon(Icons.keyboard_arrow_down_rounded, color: VisitorColors.muted),
                  ],
                ),
              ),
            ),
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => ref.refresh(visitorListProvider(query).future),
              child: AsyncView<List<Visitor>>(
                value: visitors,
                onRetry: () => ref.invalidate(visitorListProvider(query)),
                builder: (context, items) {
                  if (items.isEmpty) {
                    final filtered = _search.isNotEmpty || _status != null;
                    return ListView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      children: [
                        SizedBox(
                          height: 380,
                          child: VisitorEmptyState(
                            icon: Icons.people_alt_rounded,
                            badgeIcon: Icons.search_rounded,
                            title: filtered ? 'No matching visitors' : 'No visitors yet',
                            message: filtered
                                ? 'Try a different search, status or date range.'
                                : 'Visitors who come to your flat between these dates will show up here.',
                          ),
                        ),
                      ],
                    );
                  }

                  return ListView.separated(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.fromLTRB(16, 4, 16, 96),
                    itemCount: items.length,
                    separatorBuilder: (_, _) => const SizedBox(height: 12),
                    itemBuilder: (context, index) => _VisitorCard(visitor: items[index], query: query),
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

class _VisitorCard extends ConsumerStatefulWidget {
  const _VisitorCard({required this.visitor, required this.query});

  final Visitor visitor;
  final VisitorQuery query;

  @override
  ConsumerState<_VisitorCard> createState() => _VisitorCardState();
}

class _VisitorCardState extends ConsumerState<_VisitorCard> {
  bool _busy = false;

  Future<void> _act(Future<void> Function() action, String successMessage) async {
    setState(() => _busy = true);
    try {
      await action();
      ref.invalidate(visitorListProvider(widget.query));
      ref.invalidate(preApprovedListProvider);
      if (mounted) showVisitorSnack(context, successMessage);
    } on ApiException catch (e) {
      if (mounted) showVisitorSnack(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _confirmCancel() async {
    final visitor = widget.visitor;
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Cancel this pass?'),
        content: Text('${visitor.visitorName} will no longer be expected at the gate.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Keep')),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            style: TextButton.styleFrom(foregroundColor: VisitorColors.error),
            child: const Text('Cancel pass'),
          ),
        ],
      ),
    );
    if (confirmed == true) {
      await _act(() => ref.read(visitorRepositoryProvider).cancel(visitor.id), 'Pass cancelled');
    }
  }

  @override
  Widget build(BuildContext context) {
    final visitor = widget.visitor;
    final visual = purposeVisual(visitor.purpose);
    final flatLabel = visitor.flat?.displayLabel;

    return VisitorCardShell(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              visitor.photoUrl != null
                  ? PhotoAvatar(url: visitor.photoUrl, name: visitor.visitorName, radius: 26)
                  : CircleAvatar(
                      radius: 26,
                      backgroundColor: visual.color.withValues(alpha: 0.14),
                      child: Icon(visual.icon, color: visual.color),
                    ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(visitor.visitorName, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15.5, color: VisitorColors.text)),
                    const SizedBox(height: 2),
                    Text(
                      [if (flatLabel != null) 'Flat $flatLabel', purposeLabel(visitor.purpose)].join('  •  '),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(color: VisitorColors.muted, fontSize: 12.5),
                    ),
                    const SizedBox(height: 2),
                    Text(formatVisitorMoment(visitor.displayTime), style: const TextStyle(color: VisitorColors.muted, fontSize: 12)),
                  ],
                ),
              ),
              if (_busy)
                const SizedBox(height: 22, width: 22, child: CircularProgressIndicator(strokeWidth: 2))
              else ...[
                VisitorStatusPill(visitor: visitor),
                _VisitorMenu(
                  visitor: visitor,
                  onCancel: _confirmCancel,
                ),
              ],
            ],
          ),
          if (visitor.awaitingApproval && !_busy) ...[
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    style: OutlinedButton.styleFrom(
                      foregroundColor: VisitorColors.error,
                      side: const BorderSide(color: VisitorColors.error),
                      minimumSize: const Size.fromHeight(44),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    onPressed: () => _act(() => ref.read(visitorRepositoryProvider).reject(visitor.id), 'Visitor rejected'),
                    child: const Text('Reject'),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: FilledButton(
                    style: FilledButton.styleFrom(
                      backgroundColor: VisitorColors.success,
                      minimumSize: const Size.fromHeight(44),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    onPressed: () => _act(() => ref.read(visitorRepositoryProvider).approve(visitor.id), 'Visitor approved'),
                    child: const Text('Approve'),
                  ),
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }
}

enum _VisitorAction { viewPass, share, call, cancel }

class _VisitorMenu extends StatelessWidget {
  const _VisitorMenu({required this.visitor, required this.onCancel});

  final Visitor visitor;
  final VoidCallback onCancel;

  @override
  Widget build(BuildContext context) {
    final hasPhone = visitor.visitorPhone != null && visitor.visitorPhone!.isNotEmpty;
    final hasPass = visitor.passCode != null && visitor.isPending && !visitor.awaitingApproval;

    if (!hasPhone && !hasPass && !visitor.isCancellable) return const SizedBox(width: 8);

    return PopupMenuButton<_VisitorAction>(
      tooltip: 'More actions',
      icon: const Icon(Icons.more_vert_rounded, color: VisitorColors.muted),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      onSelected: (action) {
        switch (action) {
          case _VisitorAction.viewPass:
            context.push('/visitors/gate-pass', extra: visitor);
          case _VisitorAction.share:
            Share.share(
              'FlatCare Gate Pass for ${visitor.visitorName}: code ${visitor.passCode}. '
              'Show this code at the society gate.',
            );
          case _VisitorAction.call:
            launchUrl(Uri.parse('tel:${visitor.visitorPhone}'));
          case _VisitorAction.cancel:
            onCancel();
        }
      },
      itemBuilder: (context) => [
        if (hasPass) const PopupMenuItem(value: _VisitorAction.viewPass, child: ListTile(dense: true, leading: Icon(Icons.qr_code_2_rounded), title: Text('View pass'))),
        if (hasPass) const PopupMenuItem(value: _VisitorAction.share, child: ListTile(dense: true, leading: Icon(Icons.share_rounded), title: Text('Share pass'))),
        if (hasPhone) const PopupMenuItem(value: _VisitorAction.call, child: ListTile(dense: true, leading: Icon(Icons.call_rounded), title: Text('Call visitor'))),
        if (visitor.isCancellable)
          const PopupMenuItem(
            value: _VisitorAction.cancel,
            child: ListTile(dense: true, leading: Icon(Icons.cancel_outlined, color: VisitorColors.error), title: Text('Cancel pass', style: TextStyle(color: VisitorColors.error))),
          ),
      ],
    );
  }
}

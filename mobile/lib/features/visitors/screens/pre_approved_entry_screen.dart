import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/api/api_exception.dart';
import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/photo_avatar.dart';
import '../data/visitor.dart';
import '../data/visitor_repository.dart';
import '../providers/visitor_providers.dart';
import '../widgets/visitor_ui.dart';

/// "Pre-Approved Entry" — visitors the resident has already approved who
/// haven't turned up yet. Empty state invites the first pre-approval.
class PreApprovedEntryScreen extends ConsumerWidget {
  const PreApprovedEntryScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final entries = ref.watch(preApprovedListProvider);

    return VisitorScaffold(
      title: 'Pre-Approved Entry',
      fab: VisitorFab(tooltip: 'Pre-approve a visitor', onPressed: () => context.push('/visitors/pre-approval')),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(preApprovedListProvider.future),
        child: AsyncView<List<Visitor>>(
          value: entries,
          onRetry: () => ref.invalidate(preApprovedListProvider),
          builder: (context, items) {
            if (items.isEmpty) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                children: const [
                  SizedBox(
                    height: 480,
                    child: VisitorEmptyState(
                      icon: Icons.fact_check_rounded,
                      badgeIcon: Icons.check_rounded,
                      title: 'No Pre-Approved Entry Found',
                      message: "You haven't added any pre-approved visitor yet.",
                    ),
                  ),
                ],
              );
            }

            return ListView.separated(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 96),
              itemCount: items.length,
              separatorBuilder: (_, _) => const SizedBox(height: 12),
              itemBuilder: (context, index) => _PreApprovedCard(visitor: items[index]),
            );
          },
        ),
      ),
    );
  }
}

class _PreApprovedCard extends ConsumerWidget {
  const _PreApprovedCard({required this.visitor});

  final Visitor visitor;

  Future<void> _cancel(BuildContext context, WidgetRef ref) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Remove pre-approval?'),
        content: Text('${visitor.visitorName} will need your approval again at the gate.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Keep')),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            style: TextButton.styleFrom(foregroundColor: VisitorColors.error),
            child: const Text('Remove'),
          ),
        ],
      ),
    );
    if (confirmed != true) return;

    try {
      await ref.read(visitorRepositoryProvider).cancel(visitor.id);
      ref.invalidate(preApprovedListProvider);
      ref.invalidate(visitorListProvider);
      if (context.mounted) showVisitorSnack(context, 'Pre-approval removed');
    } on ApiException catch (e) {
      if (context.mounted) showVisitorSnack(context, e.message, error: true);
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final visual = purposeVisual(visitor.purpose);
    final phone = visitor.visitorPhone;

    return VisitorCardShell(
      child: Row(
        children: [
          visitor.photoUrl != null
              ? PhotoAvatar(url: visitor.photoUrl, name: visitor.visitorName, radius: 26)
              : CircleAvatar(radius: 26, backgroundColor: visual.color.withValues(alpha: 0.14), child: Icon(visual.icon, color: visual.color)),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(visitor.visitorName, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15.5, color: VisitorColors.text)),
                const SizedBox(height: 2),
                Text(
                  [purposeLabel(visitor.purpose), if (phone != null && phone.isNotEmpty) phone].join('  •  '),
                  style: const TextStyle(color: VisitorColors.muted, fontSize: 12.5),
                ),
                const SizedBox(height: 6),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
                  decoration: BoxDecoration(color: VisitorColors.mint, borderRadius: BorderRadius.circular(999)),
                  child: const Text('PRE-APPROVED', style: TextStyle(color: VisitorColors.green, fontWeight: FontWeight.w800, fontSize: 10.5, letterSpacing: 0.3)),
                ),
              ],
            ),
          ),
          if (phone != null && phone.isNotEmpty)
            IconButton(
              tooltip: 'Call',
              onPressed: () => launchUrl(Uri.parse('tel:$phone')),
              icon: const Icon(Icons.call_rounded, color: VisitorColors.primary),
            ),
          IconButton(
            tooltip: 'Remove',
            onPressed: () => _cancel(context, ref),
            icon: const Icon(Icons.delete_outline_rounded, color: VisitorColors.error),
          ),
        ],
      ),
    );
  }
}

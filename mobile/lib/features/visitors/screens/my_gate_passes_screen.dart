import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:share_plus/share_plus.dart';

import '../../../core/api/api_exception.dart';
import '../../../core/widgets/async_view.dart';
import '../data/visitor.dart';
import '../data/visitor_repository.dart';
import '../providers/visitor_providers.dart';
import '../widgets/pass_qr.dart';
import '../widgets/visitor_ui.dart';

/// "My Gate Passes" — the resident's dated gate passes that haven't expired
/// yet. A 5-day pass stays here for all 5 days (even after the visitor has
/// used it); each card shows its pass number and QR, and opens the full
/// Gate Pass to show or share at the gate.
class MyGatePassesScreen extends ConsumerWidget {
  const MyGatePassesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final passes = ref.watch(activeGatePassesProvider);

    return VisitorScaffold(
      title: 'My Gate Passes',
      fab: VisitorFab(tooltip: 'New Gate Pass', onPressed: () => context.push('/visitors/invite')),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(activeGatePassesProvider.future),
        child: AsyncView<List<Visitor>>(
          value: passes,
          onRetry: () => ref.invalidate(activeGatePassesProvider),
          builder: (context, items) {
            if (items.isEmpty) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                children: const [
                  SizedBox(
                    height: 480,
                    child: VisitorEmptyState(
                      icon: Icons.confirmation_number_rounded,
                      badgeIcon: Icons.qr_code_2_rounded,
                      title: 'No Active Gate Pass',
                      message: 'Create a gate pass for an expected visitor. It stays here until it expires.',
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
              itemBuilder: (context, index) => _GatePassCard(visitor: items[index]),
            );
          },
        ),
      ),
    );
  }
}

class _GatePassCard extends ConsumerWidget {
  const _GatePassCard({required this.visitor});

  final Visitor visitor;

  Future<void> _cancel(BuildContext context, WidgetRef ref) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Cancel this pass?'),
        content: Text('${visitor.visitorName} will no longer be let in with pass ${visitor.passCode}.'),
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
    if (confirmed != true) return;

    try {
      await ref.read(visitorRepositoryProvider).cancel(visitor.id);
      ref.invalidate(activeGatePassesProvider);
      ref.invalidate(visitorListProvider);
      if (context.mounted) showVisitorSnack(context, 'Pass cancelled');
    } on ApiException catch (e) {
      if (context.mounted) showVisitorSnack(context, e.message, error: true);
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final (statusLabel, statusColor) = passStatusStyle(visitor.passStatus);
    final flatLabel = visitor.flat?.displayLabel;

    return InkWell(
      borderRadius: BorderRadius.circular(18),
      onTap: () => context.push('/visitors/gate-pass', extra: visitor),
      child: VisitorCardShell(
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(4),
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: VisitorColors.border),
              ),
              child: PassQrCode(visitor: visitor, size: 72),
            ),
            const SizedBox(width: 14),
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
                  const SizedBox(height: 6),
                  Text.rich(
                    TextSpan(children: [
                      const TextSpan(text: 'Pass No. ', style: TextStyle(color: VisitorColors.muted, fontSize: 12.5)),
                      TextSpan(
                        text: visitor.passCode ?? '—',
                        style: const TextStyle(color: VisitorColors.primaryDark, fontWeight: FontWeight.w800, fontSize: 14, letterSpacing: 1.5),
                      ),
                    ]),
                  ),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      const Icon(Icons.calendar_month_rounded, size: 14, color: VisitorColors.muted),
                      const SizedBox(width: 4),
                      Expanded(
                        child: Text(
                          passValidityLabel(visitor),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(color: VisitorColors.muted, fontSize: 12),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
                    decoration: BoxDecoration(color: statusColor.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(999)),
                    child: Text(statusLabel, style: TextStyle(color: statusColor, fontWeight: FontWeight.w800, fontSize: 10.5, letterSpacing: 0.3)),
                  ),
                ],
              ),
            ),
            Column(
              children: [
                IconButton(
                  tooltip: 'Share pass',
                  onPressed: () => Share.share(
                    'FlatCare Gate Pass for ${visitor.visitorName}\n'
                    'Pass No: ${visitor.passCode}\n'
                    'Valid: ${passValidityLabel(visitor)}\n'
                    'Show this pass number or its QR code at the society gate.',
                  ),
                  icon: const Icon(Icons.share_rounded, color: VisitorColors.primary),
                ),
                if (visitor.isPending)
                  IconButton(
                    tooltip: 'Cancel pass',
                    onPressed: () => _cancel(context, ref),
                    icon: const Icon(Icons.delete_outline_rounded, color: VisitorColors.error),
                  ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

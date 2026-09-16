import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_exception.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/photo_avatar.dart';
import '../../../core/widgets/status_chip.dart';
import '../../gatekeeper/widgets/purpose_style.dart';
import '../data/visitor.dart';
import '../data/visitor_repository.dart';
import '../providers/visitor_providers.dart';

class VisitorListScreen extends ConsumerWidget {
  const VisitorListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final visitors = ref.watch(visitorListProvider(null));

    return Scaffold(
      backgroundColor: const Color(0xFFF2F3F7),
      appBar: AppBar(
        title: const Text('Visitors'),
        foregroundColor: Colors.white,
        flexibleSpace: const DecoratedBox(decoration: BoxDecoration(gradient: AppTheme.brandGradient)),
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(visitorListProvider(null).future),
        child: AsyncView<List<Visitor>>(
          value: visitors,
          onRetry: () => ref.invalidate(visitorListProvider(null)),
          builder: (context, items) {
            if (items.isEmpty) {
              return const EmptyState(message: 'No visitor entries yet for your flat.', icon: Icons.people_outline);
            }

            return ListView.separated(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              itemCount: items.length,
              separatorBuilder: (_, __) => const SizedBox(height: 8),
              itemBuilder: (context, index) => _VisitorTile(visitor: items[index]),
            );
          },
        ),
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
      ref.invalidate(visitorListProvider(null));
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
    final purposeStyle = PurposeStyle.of(visitor.purpose);

    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14), side: BorderSide(color: Colors.grey.shade200)),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Row(
              children: [
                visitor.photoUrl != null
                    ? PhotoAvatar(url: visitor.photoUrl, name: visitor.visitorName)
                    : CircleAvatar(
                        backgroundColor: purposeStyle.color.withValues(alpha: 0.15),
                        child: Text(purposeStyle.emoji, style: const TextStyle(fontSize: 17)),
                      ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(visitor.visitorName, style: const TextStyle(fontWeight: FontWeight.w700)),
                      const SizedBox(height: 2),
                      Text(
                        '${visitor.purpose.replaceAll('_', ' ')} · ${visitor.checkInAt ?? 'Not checked in yet'}',
                        style: TextStyle(color: Colors.grey.shade600, fontSize: 12.5),
                      ),
                    ],
                  ),
                ),
                _isUpdating
                    ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2))
                    : StatusChip(label: visitor.status),
              ],
            ),
            if (visitor.awaitingApproval && !_isUpdating) ...[
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton(
                      style: OutlinedButton.styleFrom(foregroundColor: const Color(0xFFE0245E), side: const BorderSide(color: Color(0xFFE0245E))),
                      onPressed: () => _act(() => ref.read(visitorRepositoryProvider).reject(visitor.id)),
                      child: const Text('Reject'),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: FilledButton(
                      style: FilledButton.styleFrom(backgroundColor: const Color(0xFF2AB930)),
                      onPressed: () => _act(() => ref.read(visitorRepositoryProvider).approve(visitor.id)),
                      child: const Text('Approve'),
                    ),
                  ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }
}

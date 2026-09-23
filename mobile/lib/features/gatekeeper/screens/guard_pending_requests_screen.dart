import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/widgets/fc/fc.dart';
import '../../visitors/data/visitor.dart';
import '../providers/gatekeeper_providers.dart';
import '../widgets/gate_visitor_card.dart';

/// Today's walk-in requests still waiting for the resident's answer. The
/// list refreshes itself every 30 seconds (and on every visitor push), so a
/// resident's Approve/Reject shows up without touching the screen.
class GuardPendingRequestsScreen extends ConsumerStatefulWidget {
  const GuardPendingRequestsScreen({super.key});

  @override
  ConsumerState<GuardPendingRequestsScreen> createState() => _GuardPendingRequestsScreenState();
}

class _GuardPendingRequestsScreenState extends ConsumerState<GuardPendingRequestsScreen> {
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    _timer = Timer.periodic(const Duration(seconds: 30), (_) => ref.invalidate(guardPendingRequestsProvider));
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final requests = ref.watch(guardPendingRequestsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Pending Requests')),
      floatingActionButton: FcFab(
        label: 'Walk-in',
        icon: Icons.person_add_alt_1_rounded,
        onPressed: () => context.push('/gatekeeper/visitors/check-in'),
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(guardPendingRequestsProvider.future),
        child: AsyncView<List<Visitor>>(
          value: requests,
          skeleton: true,
          onRetry: () => ref.invalidate(guardPendingRequestsProvider),
          builder: (context, items) {
            if (items.isEmpty) {
              return const EmptyState(
                icon: Icons.task_alt_rounded,
                color: AppColors.success,
                title: 'No pending requests',
                message: 'Every visitor request from today has been answered by the residents.',
              );
            }

            return ListView.separated(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 100),
              itemCount: items.length + 1,
              separatorBuilder: (_, __) => const SizedBox(height: 12),
              itemBuilder: (context, index) {
                if (index == 0) {
                  return GateListBanner(
                    icon: Icons.hourglass_top_rounded,
                    count: items.length,
                    caption: 'waiting for resident approval today',
                    colors: const [Color(0xFFF6B23C), AppColors.warning],
                  );
                }
                return GateVisitorCard(visitor: items[index - 1], showWaiting: true);
              },
            );
          },
        ),
      ),
    );
  }
}

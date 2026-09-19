import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/photo_avatar.dart';
import '../data/gate_keeper_repository.dart';
import '../providers/visitor_providers.dart';
import '../widgets/visitor_ui.dart';

/// "Gate Keeper" — the security guard(s) on duty right now, each with a
/// one-tap call button.
class GateKeeperScreen extends ConsumerWidget {
  const GateKeeperScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final guards = ref.watch(gateKeepersProvider);

    return VisitorScaffold(
      title: 'Gate Keeper',
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(gateKeepersProvider.future),
        child: AsyncView<GateKeepers>(
          value: guards,
          onRetry: () => ref.invalidate(gateKeepersProvider),
          builder: (context, data) {
            if (data.isEmpty) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                children: const [
                  SizedBox(
                    height: 480,
                    child: VisitorEmptyState(
                      icon: Icons.local_police_rounded,
                      badgeIcon: Icons.schedule_rounded,
                      title: 'No Gate Keeper on duty',
                      message: 'Nobody is on duty at the gate right now. Please check again in a while.',
                    ),
                  ),
                ],
              );
            }

            return ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              children: [
                if (data.day != null) _GuardCard(guard: data.day!),
                if (data.day != null && data.night != null) const SizedBox(height: 12),
                if (data.night != null) _GuardCard(guard: data.night!),
              ],
            );
          },
        ),
      ),
    );
  }
}

class _GuardCard extends StatelessWidget {
  const _GuardCard({required this.guard});

  final GateKeeper guard;

  @override
  Widget build(BuildContext context) {
    final phone = guard.phone;
    final hasPhone = phone != null && phone.isNotEmpty;

    return VisitorCardShell(
      padding: const EdgeInsets.all(16),
      child: Row(
        children: [
          PhotoAvatar(url: guard.photoUrl, name: guard.name, radius: 32),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Flexible(
                      child: Text(
                        guard.name,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 17, color: VisitorColors.text),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 3),
                      decoration: BoxDecoration(color: VisitorColors.success.withValues(alpha: 0.14), borderRadius: BorderRadius.circular(999)),
                      child: const Text('ACTIVE', style: TextStyle(color: VisitorColors.success, fontWeight: FontWeight.w800, fontSize: 10.5)),
                    ),
                  ],
                ),
                const SizedBox(height: 4),
                Text(
                  '${guard.shift.isEmpty ? '' : '${purposeLabel(guard.shift)} shift'}${hasPhone ? '${guard.shift.isEmpty ? '' : '  •  '}$phone' : ''}',
                  style: const TextStyle(color: VisitorColors.muted, fontSize: 13),
                ),
              ],
            ),
          ),
          if (hasPhone)
            IconButton.filledTonal(
              tooltip: 'Call ${guard.name}',
              onPressed: () => launchUrl(Uri.parse('tel:$phone')),
              style: IconButton.styleFrom(backgroundColor: VisitorColors.primarySoft, foregroundColor: VisitorColors.primary),
              icon: const Icon(Icons.call_rounded),
            ),
        ],
      ),
    );
  }
}

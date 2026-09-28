import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/widgets/fc/fc.dart';
import '../../visitors/data/visitor.dart';
import '../providers/gatekeeper_providers.dart';
import '../widgets/gate_visitor_card.dart';

/// Gate passes and pre-approvals residents have issued that are still
/// usable until they expire (a multi-day pass stays listed after each
/// visit) — soonest expected first. The guard scans the pass QR, or matches
/// the visitor by name, phone or pass code, and taps Allow Entry.
class GuardGatePassesScreen extends ConsumerStatefulWidget {
  const GuardGatePassesScreen({super.key});

  @override
  ConsumerState<GuardGatePassesScreen> createState() => _GuardGatePassesScreenState();
}

class _GuardGatePassesScreenState extends ConsumerState<GuardGatePassesScreen> {
  Timer? _debounce;

  @override
  void dispose() {
    _debounce?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final passes = ref.watch(gatePassListProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Gate Passes')),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => context.push('/gatekeeper/scan-pass'),
        icon: const Icon(Icons.qr_code_scanner_rounded),
        label: const Text('Scan Pass'),
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 10),
            child: TextField(
              textInputAction: TextInputAction.search,
              decoration: const InputDecoration(
                hintText: 'Search name, phone, flat or pass code',
                prefixIcon: Icon(Icons.qr_code_scanner_rounded),
              ),
              onChanged: (value) {
                _debounce?.cancel();
                _debounce = Timer(
                  const Duration(milliseconds: 350),
                  () => ref.read(gatePassSearchProvider.notifier).state = value.trim(),
                );
              },
            ),
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => ref.refresh(gatePassListProvider.future),
              child: AsyncView<List<Visitor>>(
                value: passes,
                skeleton: true,
                onRetry: () => ref.invalidate(gatePassListProvider),
                builder: (context, items) {
                  if (items.isEmpty) {
                    return const EmptyState(
                      icon: Icons.confirmation_number_outlined,
                      color: AppColors.accentIndigo,
                      title: 'No active gate passes',
                      message: 'Passes that residents create for their guests will appear here until they expire.',
                    );
                  }

                  return ListView.separated(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.fromLTRB(16, 4, 16, 96),
                    itemCount: items.length + 1,
                    separatorBuilder: (_, _) => const SizedBox(height: 12),
                    itemBuilder: (context, index) {
                      if (index == 0) {
                        return GateListBanner(
                          icon: Icons.confirmation_number_rounded,
                          count: items.length,
                          caption: 'active gate passes issued by residents',
                          colors: const [Color(0xFF7B7BE8), AppColors.accentIndigo],
                        );
                      }
                      return GateVisitorCard(visitor: items[index - 1]);
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

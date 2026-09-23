import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/models/flat.dart';
import '../../../core/widgets/fc/fc.dart';
import '../providers/gatekeeper_providers.dart';
import '../widgets/gate_visitor_card.dart';

/// Houses the resident has marked closed (Visitor Settings › House Closed)
/// — nobody may be let in, and walk-in check-in refuses these flats. The
/// guard can call the resident straight from the card.
class GuardClosedHousesScreen extends ConsumerStatefulWidget {
  const GuardClosedHousesScreen({super.key});

  @override
  ConsumerState<GuardClosedHousesScreen> createState() => _GuardClosedHousesScreenState();
}

class _GuardClosedHousesScreenState extends ConsumerState<GuardClosedHousesScreen> {
  Timer? _debounce;

  @override
  void dispose() {
    _debounce?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final houses = ref.watch(closedHouseListProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Closed Houses')),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 10),
            child: TextField(
              textInputAction: TextInputAction.search,
              decoration: const InputDecoration(hintText: 'Search block, flat or owner', prefixIcon: Icon(Icons.search_rounded)),
              onChanged: (value) {
                _debounce?.cancel();
                _debounce = Timer(
                  const Duration(milliseconds: 350),
                  () => ref.read(closedHouseSearchProvider.notifier).state = value.trim(),
                );
              },
            ),
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => ref.refresh(closedHouseListProvider.future),
              child: AsyncView<List<Flat>>(
                value: houses,
                skeleton: true,
                onRetry: () => ref.invalidate(closedHouseListProvider),
                builder: (context, items) {
                  if (items.isEmpty) {
                    return const EmptyState(
                      icon: Icons.lock_open_rounded,
                      color: AppColors.success,
                      title: 'No closed houses',
                      message: 'When a resident marks their house closed (e.g. out of town), it appears here.',
                    );
                  }

                  return ListView.separated(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.fromLTRB(16, 4, 16, 32),
                    itemCount: items.length + 1,
                    separatorBuilder: (_, __) => const SizedBox(height: 12),
                    itemBuilder: (context, index) {
                      if (index == 0) {
                        return GateListBanner(
                          icon: Icons.lock_rounded,
                          count: items.length,
                          caption: 'houses closed — do not allow entry',
                          colors: const [Color(0xFFEF6B75), AppColors.danger],
                        );
                      }
                      return _ClosedHouseCard(flat: items[index - 1]);
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

class _ClosedHouseCard extends StatelessWidget {
  const _ClosedHouseCard({required this.flat});

  final Flat flat;

  @override
  Widget build(BuildContext context) {
    final residents = flat.residents.where((r) => (r.name ?? '').isNotEmpty).toList();
    final callable = flat.residents.where((r) => (r.phone ?? '').isNotEmpty).firstOrNull;
    final owner = residents.isNotEmpty ? residents.first.name! : (flat.ownerName ?? 'Owner not on file');

    return FcListCard(
      leading: const FcIconBox(icon: Icons.lock_rounded, color: AppColors.danger),
      title: flat.displayLabel,
      meta: [
        FcMeta(Icons.person_outline_rounded, owner),
        if (residents.length > 1) FcMeta(Icons.groups_2_outlined, '+${residents.length - 1} more residents'),
        if (callable != null) FcMeta(Icons.phone_outlined, callable.phone!),
      ],
      badges: const [
        FcBadge(label: 'House closed', color: AppColors.danger, icon: Icons.do_not_disturb_on_rounded),
        FcBadge(label: 'No entry', color: AppColors.warning, icon: Icons.front_hand_rounded),
      ],
      trailing: callable == null
          ? null
          : Padding(
              padding: const EdgeInsets.only(right: 6),
              child: FcCardAction(
                icon: Icons.call_rounded,
                color: AppColors.success,
                tooltip: 'Call resident',
                onPressed: () => launchUrl(Uri.parse('tel:${callable.phone}')),
              ),
            ),
    );
  }
}

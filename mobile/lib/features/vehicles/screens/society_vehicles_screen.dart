import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/utils/vehicle_types.dart';
import '../../../core/widgets/fc/fc.dart';
import '../data/society_vehicles.dart';

/// Owner / tenant ("Rent") / occupant - label and accent on each card.
(String, Color) _residentStyle(String type) => switch (type.toLowerCase()) {
      'owner' => ('Owner', AppColors.accentSky),
      'tenant' => ('Rent', AppColors.accentAmber),
      'occupant' => ('Occupant', AppColors.accentViolet),
      _ => ('Resident', AppColors.accentSlate),
    };

Future<void> _call(String phone) => launchUrl(Uri.parse('tel:$phone'));

Future<void> _whatsApp(String phone) {
  var digits = phone.replaceAll(RegExp(r'\D'), '');
  if (digits.length == 10) digits = '91$digits';
  return launchUrl(Uri.parse('https://wa.me/$digits'), mode: LaunchMode.externalApplication);
}

/// Society-wide vehicle list: pick a block, see how many cars / bikes /
/// scooters it has, and every resident's vehicles with call / WhatsApp.
/// "My Vehicles" at the bottom opens the user's own vehicles to add or edit.
class SocietyVehiclesScreen extends ConsumerStatefulWidget {
  const SocietyVehiclesScreen({super.key});

  @override
  ConsumerState<SocietyVehiclesScreen> createState() => _SocietyVehiclesScreenState();
}

class _SocietyVehiclesScreenState extends ConsumerState<SocietyVehiclesScreen> {
  Timer? _debounce;

  @override
  void dispose() {
    _debounce?.cancel();
    super.dispose();
  }

  void _onSearch(String value) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 350), () {
      ref.read(vehicleSearchProvider.notifier).state = value.trim();
    });
  }

  @override
  Widget build(BuildContext context) {
    final data = ref.watch(societyVehiclesProvider);
    // Keep the block tabs on screen while the next block loads.
    final blocks = data.valueOrNull?.blocks ?? const <VehicleBlock>[];
    final selectedBlock = ref.watch(vehicleBlockProvider) ?? data.valueOrNull?.blockId;

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Vehicles', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800)),
        centerTitle: true,
        iconTheme: const IconThemeData(color: Colors.white),
        flexibleSpace: Container(decoration: const BoxDecoration(gradient: AppColors.primaryGradient)),
      ),
      bottomNavigationBar: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
          child: _GradientButton(
            label: 'My Vehicles',
            icon: Icons.garage_rounded,
            onPressed: () async {
              await context.push('/profile/vehicles');
              ref.invalidate(societyVehiclesProvider);
            },
          ),
        ),
      ),
      body: Column(
        children: [
          Container(
            color: AppColors.background,
            padding: const EdgeInsets.fromLTRB(16, 14, 16, 10),
            child: TextField(
              onChanged: _onSearch,
              textInputAction: TextInputAction.search,
              decoration: InputDecoration(
                hintText: 'Search name, flat or vehicle number',
                prefixIcon: const Icon(Icons.search_rounded),
                filled: true,
                fillColor: Colors.white,
                isDense: true,
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: BorderSide.none),
              ),
            ),
          ),
          if (blocks.isNotEmpty)
            SizedBox(
              height: 46,
              child: ListView.separated(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 16),
                itemCount: blocks.length,
                separatorBuilder: (_, _) => const SizedBox(width: 10),
                itemBuilder: (context, i) => _BlockTab(
                  block: blocks[i],
                  selected: blocks[i].id == selectedBlock,
                  onTap: () => ref.read(vehicleBlockProvider.notifier).state = blocks[i].id,
                ),
              ),
            ),
          const SizedBox(height: 8),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => ref.refresh(societyVehiclesProvider.future),
              child: AsyncView<SocietyVehicles>(
                value: data,
                skeleton: true,
                onRetry: () => ref.invalidate(societyVehiclesProvider),
                builder: (context, d) {
                  final searching = ref.read(vehicleSearchProvider).isNotEmpty;
                  return ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
                    children: [
                      if (d.counts.isNotEmpty) ...[
                        _CountsCard(counts: d.counts),
                        const SizedBox(height: 14),
                      ],
                      if (d.residents.isEmpty)
                        Padding(
                          padding: const EdgeInsets.only(top: 40),
                          child: EmptyState(
                            icon: searching ? Icons.search_off_rounded : Icons.directions_car_filled_rounded,
                            title: searching ? 'No matches' : 'No vehicles yet',
                            message: searching
                                ? 'Try another name, flat or vehicle number.'
                                : 'Nobody in this block has registered a vehicle.',
                          ),
                        )
                      else
                        for (final owner in d.residents) ...[
                          _OwnerCard(owner: owner),
                          const SizedBox(height: 12),
                        ],
                    ],
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

class _BlockTab extends StatelessWidget {
  const _BlockTab({required this.block, required this.selected, required this.onTap});

  final VehicleBlock block;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(23),
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        constraints: const BoxConstraints(minWidth: 64),
        padding: const EdgeInsets.symmetric(horizontal: 18),
        alignment: Alignment.center,
        decoration: BoxDecoration(
          gradient: selected ? AppColors.primaryGradient : null,
          color: selected ? null : Colors.white,
          borderRadius: BorderRadius.circular(23),
          border: Border.all(color: selected ? Colors.transparent : AppColors.border),
          boxShadow: selected
              ? [BoxShadow(color: AppColors.primary.withValues(alpha: 0.35), blurRadius: 10, offset: const Offset(0, 4))]
              : null,
        ),
        child: Text(
          block.name,
          style: TextStyle(
            fontSize: 15,
            fontWeight: FontWeight.w800,
            color: selected ? Colors.white : AppColors.textPrimary,
          ),
        ),
      ),
    );
  }
}

class _CountsCard extends StatelessWidget {
  const _CountsCard({required this.counts});

  final List<VehicleTypeCount> counts;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(20), boxShadow: kCardShadow),
      // Up to four types share the width; more than that scroll sideways.
      child: counts.length <= 4
          ? Row(
              children: [
                for (final c in counts) ...[
                  Expanded(child: _CountTile(count: c)),
                  if (c != counts.last) const SizedBox(width: 8),
                ],
              ],
            )
          : SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: Row(
                children: [
                  for (final c in counts) ...[
                    SizedBox(width: 78, child: _CountTile(count: c)),
                    if (c != counts.last) const SizedBox(width: 8),
                  ],
                ],
              ),
            ),
    );
  }
}

class _CountTile extends StatelessWidget {
  const _CountTile({required this.count});

  final VehicleTypeCount count;

  @override
  Widget build(BuildContext context) {
    final style = vehicleTypeStyle(count.type);
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 10),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [style.color.withValues(alpha: 0.16), style.color.withValues(alpha: 0.05)],
        ),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: style.color.withValues(alpha: 0.25)),
      ),
      child: Column(
        children: [
          Icon(style.icon, color: style.color, size: 22),
          const SizedBox(height: 4),
          Text('${count.count}', style: TextStyle(fontSize: 20, fontWeight: FontWeight.w900, color: style.color)),
          Text(
            style.label,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w600, color: AppColors.textSecondary),
          ),
        ],
      ),
    );
  }
}

class _OwnerCard extends StatelessWidget {
  const _OwnerCard({required this.owner});

  final VehicleOwner owner;

  @override
  Widget build(BuildContext context) {
    final (typeLabel, color) = _residentStyle(owner.residentType);
    final phone = owner.phone;
    final callable = phone != null && phone.isNotEmpty && !phone.contains('*');

    return Container(
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(20), boxShadow: kCardShadow),
      clipBehavior: Clip.antiAlias,
      child: IntrinsicHeight(
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Container(width: 5, color: color),
            Expanded(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(12, 12, 12, 12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        FcInitialsAvatar(name: owner.name, color: color, size: 48),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                owner.name,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: AppColors.textPrimary),
                              ),
                              const SizedBox(height: 3),
                              Row(
                                children: [
                                  Text(owner.flatNumber, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: AppColors.textSecondary)),
                                  const SizedBox(width: 8),
                                  Container(width: 7, height: 7, decoration: BoxDecoration(color: color, shape: BoxShape.circle)),
                                  const SizedBox(width: 4),
                                  Text(typeLabel, style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: color)),
                                ],
                              ),
                            ],
                          ),
                        ),
                        if (callable) ...[
                          _RoundAction(
                            icon: Icons.call_rounded,
                            color: AppColors.info,
                            filled: false,
                            tooltip: 'Call ${owner.name}',
                            onTap: () => _call(phone),
                          ),
                          const SizedBox(width: 8),
                          _RoundAction(
                            icon: Icons.chat_rounded,
                            color: const Color(0xFF25D366),
                            filled: true,
                            tooltip: 'WhatsApp ${owner.name}',
                            onTap: () => _whatsApp(phone),
                          ),
                        ],
                      ],
                    ),
                    const SizedBox(height: 10),
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: [for (final v in owner.vehicles) _VehicleChip(vehicle: v)],
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _RoundAction extends StatelessWidget {
  const _RoundAction({required this.icon, required this.color, required this.filled, required this.tooltip, required this.onTap});

  final IconData icon;
  final Color color;
  final bool filled;
  final String tooltip;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Tooltip(
      message: tooltip,
      child: Material(
        color: filled ? color : color.withValues(alpha: 0.12),
        shape: const CircleBorder(),
        child: InkWell(
          customBorder: const CircleBorder(),
          onTap: onTap,
          child: SizedBox(width: 42, height: 42, child: Icon(icon, size: 21, color: filled ? Colors.white : color)),
        ),
      ),
    );
  }
}

class _VehicleChip extends StatelessWidget {
  const _VehicleChip({required this.vehicle});

  final ResidentVehicle vehicle;

  @override
  Widget build(BuildContext context) {
    final style = vehicleTypeStyle(vehicle.type);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: style.color.withValues(alpha: 0.10),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: style.color.withValues(alpha: 0.35)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(style.icon, size: 18, color: style.color),
          const SizedBox(width: 6),
          Text(
            vehicle.registrationNumber,
            style: TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: style.color, letterSpacing: 0.3),
          ),
        ],
      ),
    );
  }
}

class _GradientButton extends StatelessWidget {
  const _GradientButton({required this.label, required this.icon, required this.onPressed});

  final String label;
  final IconData icon;
  final VoidCallback onPressed;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        gradient: AppColors.primaryGradient,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [BoxShadow(color: AppColors.primary.withValues(alpha: 0.35), blurRadius: 12, offset: const Offset(0, 5))],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(16),
          onTap: onPressed,
          child: SizedBox(
            height: 54,
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(icon, color: Colors.white),
                const SizedBox(width: 8),
                Text(label, style: const TextStyle(color: Colors.white, fontSize: 16.5, fontWeight: FontWeight.w800)),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

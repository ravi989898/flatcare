import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/utils/vehicle_types.dart';
import '../../../core/widgets/fc/fc.dart';
import '../data/guard_vehicle.dart';
import '../providers/gatekeeper_providers.dart';

/// Society-wide, read-only vehicle register — lets a guard confirm a
/// vehicle at the gate is actually registered to a resident. Modeled on
/// features/directory/screens/directory_list_screen.dart's search pattern.
class GatekeeperVehicleListScreen extends ConsumerWidget {
  const GatekeeperVehicleListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final vehicles = ref.watch(guardVehicleListProvider);

    return Scaffold(
            appBar: AppBar(
        title: const Text('Vehicles'),
        foregroundColor: Colors.white,
        flexibleSpace: const DecoratedBox(decoration: BoxDecoration(gradient: AppTheme.brandGradient)),
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: TextField(
              decoration: const InputDecoration(
                labelText: 'Search by registration number or owner',
                prefixIcon: Icon(Icons.search),
              ),
              onChanged: (value) => ref.read(guardVehicleSearchProvider.notifier).state = value,
            ),
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => ref.refresh(guardVehicleListProvider.future),
              child: AsyncView<List<GuardVehicle>>(
                value: vehicles,
                skeleton: true,
                onRetry: () => ref.invalidate(guardVehicleListProvider),
                builder: (context, items) {
                  if (items.isEmpty) {
                    return const EmptyState(
                      title: 'No vehicles found',
                      message: 'No registered vehicle matches your search.',
                      icon: Icons.directions_car_filled_rounded,
                    );
                  }

                  return ListView.separated(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    itemCount: items.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 12),
                    itemBuilder: (context, index) {
                      final item = items[index];
                      final style = vehicleTypeStyle(item.vehicleType);

                      return FcListCard(
                        onTap: () => context.push('/gatekeeper/vehicles/detail', extra: item),
                        leading: FcIconBox(icon: style.icon, color: style.color, size: 52),
                        title: item.registrationNumber,
                        titleStyle: const TextStyle(
                          fontSize: 17,
                          fontWeight: FontWeight.w800,
                          letterSpacing: 0.8,
                          color: AppColors.textPrimary,
                        ),
                        subtitle: item.model,
                        meta: [
                          if (item.ownerName != null) FcMeta(Icons.person_outline_rounded, item.ownerName!),
                          if (item.flatLabel != null) FcMeta(Icons.apartment_rounded, item.flatLabel!),
                        ],
                        badges: [
                          FcBadge(label: style.label, color: style.color),
                          if (item.parkingSlot != null && item.parkingSlot!.isNotEmpty)
                            FcBadge(label: 'Slot ${item.parkingSlot}', icon: Icons.local_parking_rounded),
                        ],
                        trailing: const Padding(
                          padding: EdgeInsets.only(top: 12, right: 4),
                          child: Icon(Icons.chevron_right_rounded, color: AppColors.textMuted),
                        ),
                      );
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

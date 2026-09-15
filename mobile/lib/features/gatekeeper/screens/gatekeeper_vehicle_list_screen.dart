import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/async_view.dart';
import '../data/guard_vehicle.dart';
import '../providers/gatekeeper_providers.dart';

/// A small palette cycled by vehicle type initial, purely so the list
/// doesn't read as a flat wall of identical grey icons — mirrors the accent
/// coloring used on the visitor log and home screen tiles.
const _vehicleColors = [Color(0xFF1783C0), Color(0xFF8E5FE0), Color(0xFF2AB98A), Color(0xFFF5A623)];

Color _colorForVehicleType(String vehicleType) => _vehicleColors[vehicleType.hashCode.abs() % _vehicleColors.length];

/// Society-wide, read-only vehicle register — lets a guard confirm a
/// vehicle at the gate is actually registered to a resident. Modeled on
/// features/directory/screens/directory_list_screen.dart's search pattern.
class GatekeeperVehicleListScreen extends ConsumerWidget {
  const GatekeeperVehicleListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final vehicles = ref.watch(guardVehicleListProvider);

    return Scaffold(
      backgroundColor: const Color(0xFFF2F3F7),
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
                onRetry: () => ref.invalidate(guardVehicleListProvider),
                builder: (context, items) {
                  if (items.isEmpty) {
                    return const EmptyState(message: 'No vehicles found.', icon: Icons.directions_car_outlined);
                  }

                  return ListView.separated(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    itemCount: items.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 8),
                    itemBuilder: (context, index) {
                      final item = items[index];
                      final color = _colorForVehicleType(item.vehicleType);

                      return Card(
                        elevation: 0,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14), side: BorderSide(color: Colors.grey.shade200)),
                        child: ListTile(
                          onTap: () => context.push('/gatekeeper/vehicles/detail', extra: item),
                          leading: CircleAvatar(
                            backgroundColor: color.withValues(alpha: 0.15),
                            child: Icon(Icons.directions_car_outlined, color: color),
                          ),
                          title: Text(item.registrationNumber, style: const TextStyle(fontWeight: FontWeight.w700)),
                          subtitle: Padding(
                            padding: const EdgeInsets.only(top: 4),
                            child: Text(
                              [
                                item.vehicleType,
                                if (item.model != null) item.model!,
                                if (item.ownerName != null) item.ownerName!,
                                if (item.flatLabel != null) item.flatLabel!,
                              ].join(' · '),
                            ),
                          ),
                          trailing: const Icon(Icons.chevron_right, color: Colors.black38),
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

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_exception.dart';
import '../../../core/widgets/async_view.dart';
import '../data/profile_repository.dart';
import '../data/vehicle.dart';
import '../providers/profile_providers.dart';

class VehiclesScreen extends ConsumerWidget {
  const VehiclesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final vehicles = ref.watch(vehicleListProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Vehicles')),
      floatingActionButton: FloatingActionButton(
        onPressed: () => _showAddDialog(context, ref),
        child: const Icon(Icons.add),
      ),
      body: AsyncView<List<Vehicle>>(
        value: vehicles,
        onRetry: () => ref.invalidate(vehicleListProvider),
        builder: (context, items) {
          if (items.isEmpty) {
            return const EmptyState(message: 'No vehicles added yet.', icon: Icons.directions_car_outlined);
          }

          return ListView.separated(
            padding: const EdgeInsets.all(16),
            itemCount: items.length,
            separatorBuilder: (_, __) => const SizedBox(height: 8),
            itemBuilder: (context, index) {
              final vehicle = items[index];

              return Card(
                child: ListTile(
                  leading: const Icon(Icons.directions_car_outlined),
                  title: Text(vehicle.registrationNumber),
                  subtitle: Text(
                    [vehicle.vehicleType, vehicle.model, vehicle.parkingSlot].whereType<String>().join(' · '),
                  ),
                  trailing: IconButton(
                    icon: const Icon(Icons.delete_outline),
                    onPressed: () async {
                      try {
                        await ref.read(profileRepositoryProvider).deleteVehicle(vehicle.id);
                        ref.invalidate(vehicleListProvider);
                      } on ApiException catch (e) {
                        if (context.mounted) {
                          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
                        }
                      }
                    },
                  ),
                ),
              );
            },
          );
        },
      ),
    );
  }

  Future<void> _showAddDialog(BuildContext context, WidgetRef ref) async {
    final typeController = TextEditingController();
    final regController = TextEditingController();
    final slotController = TextEditingController();
    final formKey = GlobalKey<FormState>();

    await showDialog(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Add Vehicle'),
        content: Form(
          key: formKey,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextFormField(
                controller: typeController,
                decoration: const InputDecoration(labelText: 'Type (car, bike…)'),
                validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
              ),
              TextFormField(
                controller: regController,
                decoration: const InputDecoration(labelText: 'Registration number'),
                validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
              ),
              TextFormField(
                controller: slotController,
                decoration: const InputDecoration(labelText: 'Parking slot (optional)'),
              ),
            ],
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.of(dialogContext).pop(), child: const Text('Cancel')),
          TextButton(
            onPressed: () async {
              if (!formKey.currentState!.validate()) return;
              try {
                await ref.read(profileRepositoryProvider).addVehicle({
                  'vehicle_type': typeController.text.trim(),
                  'registration_number': regController.text.trim(),
                  if (slotController.text.trim().isNotEmpty) 'parking_slot': slotController.text.trim(),
                });
                ref.invalidate(vehicleListProvider);
                if (dialogContext.mounted) Navigator.of(dialogContext).pop();
              } on ApiException catch (e) {
                if (dialogContext.mounted) {
                  ScaffoldMessenger.of(dialogContext).showSnackBar(SnackBar(content: Text(e.message)));
                }
              }
            },
            child: const Text('Add'),
          ),
        ],
      ),
    );
  }
}

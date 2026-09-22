import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_exception.dart';
import '../../../core/widgets/app_form_dialog.dart';
import '../../../core/widgets/async_view.dart';
import '../data/profile_repository.dart';
import '../data/vehicle.dart';
import '../providers/profile_providers.dart';

/// Predefined choices for the Add Vehicle "Type" field — 'Other' stays
/// free-text since a fixed list can never cover every vehicle a gate
/// might see.
const List<String> _vehicleTypes = ['Car', 'Bike', 'Scooter', 'Auto', 'Van', 'Truck', 'Other'];

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
    final otherTypeController = TextEditingController();
    final regController = TextEditingController();
    final slotController = TextEditingController();
    final formKey = GlobalKey<FormState>();
    String? selectedType = _vehicleTypes.first;

    final saved = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => StatefulBuilder(
        builder: (statefulContext, setState) {
          return AppFormDialog(
            title: 'Add Vehicle',
            formKey: formKey,
            submitLabel: 'Add',
            fields: [
              DropdownButtonFormField<String>(
                initialValue: selectedType,
                decoration: const InputDecoration(labelText: 'Vehicle Type'),
                items: [
                  for (final type in _vehicleTypes) DropdownMenuItem(value: type, child: Text(type)),
                ],
                onChanged: (value) => setState(() => selectedType = value),
                validator: (value) => (value == null || value.isEmpty) ? 'Select a vehicle type' : null,
              ),
              if (selectedType == 'Other')
                TextFormField(
                  controller: otherTypeController,
                  decoration: const InputDecoration(labelText: 'Specify vehicle type'),
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
            onSubmit: () async {
              final vehicleType = selectedType == 'Other' ? otherTypeController.text.trim() : selectedType!;
              await ref.read(profileRepositoryProvider).addVehicle({
                'vehicle_type': vehicleType,
                'registration_number': regController.text.trim(),
                if (slotController.text.trim().isNotEmpty) 'parking_slot': slotController.text.trim(),
              });
            },
          );
        },
      ),
    );

    if (saved == true) ref.invalidate(vehicleListProvider);
  }
}

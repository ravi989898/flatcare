import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_exception.dart';
import '../../../core/utils/vehicle_types.dart';
import '../../../core/widgets/fc/fc.dart';
import '../data/profile_repository.dart';
import '../data/vehicle.dart';
import '../providers/profile_providers.dart';

final _registrationPattern = RegExp(r'^[A-Z0-9 -]{4,15}$');

class VehiclesScreen extends ConsumerWidget {
  const VehiclesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final vehicles = ref.watch(vehicleListProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('My Vehicles')),
      floatingActionButton: FcFab(label: 'Add Vehicle', onPressed: () => _openForm(context, ref)),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(vehicleListProvider.future),
        child: AsyncView<List<Vehicle>>(
          value: vehicles,
          skeleton: true,
          onRetry: () => ref.invalidate(vehicleListProvider),
          builder: (context, items) {
            if (items.isEmpty) {
              return EmptyState(
                icon: Icons.directions_car_filled_rounded,
                title: 'No vehicles registered',
                message: 'Register your car, bike or scooter so security can recognise it at the gate.',
                action: FilledButton.icon(
                  onPressed: () => _openForm(context, ref),
                  icon: const Icon(Icons.add_rounded),
                  label: const Text('Add Vehicle'),
                ),
              );
            }

            return ListView.separated(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 100),
              itemCount: items.length,
              separatorBuilder: (_, __) => const SizedBox(height: 12),
              itemBuilder: (context, index) => _VehicleCard(
                vehicle: items[index],
                onEdit: () => _openForm(context, ref, vehicle: items[index]),
                onDelete: () => _confirmDelete(context, ref, items[index]),
              ),
            );
          },
        ),
      ),
    );
  }

  Future<void> _confirmDelete(BuildContext context, WidgetRef ref, Vehicle vehicle) async {
    final confirmed = await showFcConfirmDialog(
      context,
      title: 'Delete vehicle?',
      message: 'Are you sure you want to remove ${vehicle.registrationNumber} from your registered vehicles?',
      confirmLabel: 'Delete',
      danger: true,
    );
    if (!confirmed) return;

    try {
      await ref.read(profileRepositoryProvider).deleteVehicle(vehicle.id);
      ref.invalidate(vehicleListProvider);
      if (context.mounted) showFcSnack(context, '${vehicle.registrationNumber} removed.');
    } on ApiException catch (e) {
      if (context.mounted) showFcSnack(context, e.message, error: true);
    }
  }

  Future<void> _openForm(BuildContext context, WidgetRef ref, {Vehicle? vehicle}) async {
    final isEdit = vehicle != null;
    final regController = TextEditingController(text: vehicle?.registrationNumber);
    final modelController = TextEditingController(text: vehicle?.model);
    final slotController = TextEditingController(text: vehicle?.parkingSlot);
    final otherController = TextEditingController();

    String? type;
    if (vehicle != null) {
      final known = knownVehicleType(vehicle.vehicleType);
      if (known != null) {
        type = known.label;
      } else {
        type = vehicleTypeOther;
        otherController.text = vehicleTypeStyle(vehicle.vehicleType).label;
      }
    }

    final saved = await FcFormSheet.show(
      context,
      title: isEdit ? 'Edit Vehicle' : 'Add Vehicle',
      subtitle: isEdit ? vehicle.registrationNumber : 'Register a vehicle for your flat',
      icon: Icons.directions_car_filled_rounded,
      submitLabel: isEdit ? 'Save Changes' : 'Add Vehicle',
      fieldsBuilder: (context, setState, errors) => [
        FcSelectField(
          label: 'Vehicle type',
          initialValue: type,
          prefixIcon: Icons.category_outlined,
          errorText: type == vehicleTypeOther ? null : errors['vehicle_type'],
          options: [for (final t in vehicleTypes) FcOption(t.label, t.label, icon: t.icon, color: t.color)],
          onChanged: (value) => setState(() => type = value),
        ),
        if (type == vehicleTypeOther)
          TextFormField(
            controller: otherController,
            textCapitalization: TextCapitalization.words,
            decoration: InputDecoration(
              labelText: 'Enter vehicle type *',
              hintText: 'e.g. Tractor, E-rickshaw',
              prefixIcon: const Icon(Icons.edit_outlined),
              errorText: errors['vehicle_type'],
            ),
            validator: (v) {
              final value = v?.trim() ?? '';
              if (value.isEmpty) return 'Please enter the vehicle type';
              if (value.length > 50) return 'Vehicle type is too long';
              return null;
            },
          ),
        TextFormField(
          controller: regController,
          textCapitalization: TextCapitalization.characters,
          inputFormatters: [_UpperCaseFormatter()],
          decoration: InputDecoration(
            labelText: 'Vehicle number *',
            hintText: 'e.g. GJ01AB1234',
            prefixIcon: const Icon(Icons.pin_outlined),
            errorText: errors['registration_number'],
          ),
          validator: (v) {
            final value = v?.trim() ?? '';
            if (value.isEmpty) return 'Please enter the vehicle number';
            return _registrationPattern.hasMatch(value) ? null : 'Use letters and numbers only (4–15 characters)';
          },
        ),
        TextFormField(
          controller: modelController,
          textCapitalization: TextCapitalization.words,
          decoration: InputDecoration(
            labelText: 'Make / model (optional)',
            hintText: 'e.g. Honda City',
            prefixIcon: const Icon(Icons.info_outline_rounded),
            errorText: errors['model'],
          ),
        ),
        TextFormField(
          controller: slotController,
          decoration: InputDecoration(
            labelText: 'Parking slot (optional)',
            hintText: 'e.g. P-12',
            prefixIcon: const Icon(Icons.local_parking_rounded),
            errorText: errors['parking_slot'],
          ),
        ),
      ],
      onSubmit: () async {
        String? optional(TextEditingController c) => c.text.trim().isEmpty ? null : c.text.trim();
        final payload = <String, dynamic>{
          'vehicle_type': type == vehicleTypeOther ? otherController.text.trim() : type,
          'registration_number': regController.text.trim(),
          'model': optional(modelController),
          'parking_slot': optional(slotController),
        };
        final repository = ref.read(profileRepositoryProvider);
        if (isEdit) {
          await repository.updateVehicle(vehicle.id, payload);
        } else {
          await repository.addVehicle(payload);
        }
      },
    );

    if (saved) {
      ref.invalidate(vehicleListProvider);
      if (context.mounted) showFcSnack(context, isEdit ? 'Vehicle updated.' : 'Vehicle added.');
    }
  }
}

class _VehicleCard extends StatelessWidget {
  const _VehicleCard({required this.vehicle, required this.onEdit, required this.onDelete});

  final Vehicle vehicle;
  final VoidCallback onEdit;
  final VoidCallback onDelete;

  @override
  Widget build(BuildContext context) {
    final style = vehicleTypeStyle(vehicle.vehicleType);

    return FcListCard(
      leading: FcIconBox(icon: style.icon, color: style.color, size: 52),
      title: vehicle.registrationNumber,
      titleStyle: const TextStyle(fontSize: 17, fontWeight: FontWeight.w800, letterSpacing: 0.8, color: AppColors.textPrimary),
      subtitle: vehicle.model,
      badges: [
        FcBadge(label: style.label, color: style.color),
        if (vehicle.parkingSlot != null && vehicle.parkingSlot!.isNotEmpty)
          FcBadge(label: 'Slot ${vehicle.parkingSlot}', color: AppColors.primary, icon: Icons.local_parking_rounded),
      ],
      onTap: onEdit,
      trailing: FcCardMenu(
        items: [
          FcCardMenuItem(label: 'Edit', icon: Icons.edit_outlined, onSelected: onEdit),
          FcCardMenuItem(label: 'Delete', icon: Icons.delete_outline_rounded, danger: true, onSelected: onDelete),
        ],
      ),
    );
  }
}

class _UpperCaseFormatter extends TextInputFormatter {
  @override
  TextEditingValue formatEditUpdate(TextEditingValue oldValue, TextEditingValue newValue) =>
      newValue.copyWith(text: newValue.text.toUpperCase());
}

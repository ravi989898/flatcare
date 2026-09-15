/// A vehicle on the society-wide gate register (Api\V1\Guard\VehicleController)
/// — the guard-facing counterpart of features/profile/data/vehicle.dart,
/// which is scoped to the resident's own vehicles only.
class GuardVehicle {
  GuardVehicle({
    required this.id,
    required this.vehicleType,
    required this.registrationNumber,
    this.model,
    this.color,
    this.year,
    this.parkingSlot,
    required this.status,
    this.ownerName,
    this.ownerPhone,
    this.flatLabel,
  });

  factory GuardVehicle.fromJson(Map<String, dynamic> json) {
    return GuardVehicle(
      id: json['id'] as int,
      vehicleType: json['vehicle_type'] as String,
      registrationNumber: json['registration_number'] as String,
      model: json['model'] as String?,
      color: json['color'] as String?,
      year: json['year']?.toString(),
      parkingSlot: json['parking_slot'] as String?,
      status: json['status'] as String? ?? 'active',
      ownerName: json['owner_name'] as String?,
      ownerPhone: json['owner_phone'] as String?,
      flatLabel: json['flat_label'] as String?,
    );
  }

  final int id;
  final String vehicleType;
  final String registrationNumber;
  final String? model;
  final String? color;
  final String? year;
  final String? parkingSlot;
  final String status;
  final String? ownerName;
  final String? ownerPhone;
  final String? flatLabel;
}

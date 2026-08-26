class Vehicle {
  Vehicle({
    required this.id,
    required this.vehicleType,
    required this.registrationNumber,
    this.model,
    this.color,
    this.year,
    this.parkingSlot,
    required this.status,
  });

  factory Vehicle.fromJson(Map<String, dynamic> json) {
    return Vehicle(
      id: json['id'] as int,
      vehicleType: json['vehicle_type'] as String,
      registrationNumber: json['registration_number'] as String,
      model: json['model'] as String?,
      color: json['color'] as String?,
      year: json['year'] as int?,
      parkingSlot: json['parking_slot'] as String?,
      status: json['status'] as String? ?? 'active',
    );
  }

  final int id;
  final String vehicleType;
  final String registrationNumber;
  final String? model;
  final String? color;
  final int? year;
  final String? parkingSlot;
  final String status;
}

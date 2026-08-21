import 'flat.dart';

/// One row from UserResource.flats — a resident's link to a flat
/// (mirrors the FlatResident model on the backend).
class Residency {
  Residency({
    required this.residencyId,
    required this.residentType,
    required this.isPrimary,
    required this.flat,
  });

  factory Residency.fromJson(Map<String, dynamic> json) {
    return Residency(
      residencyId: json['residency_id'] as int,
      residentType: json['resident_type'] as String,
      isPrimary: json['is_primary'] as bool? ?? false,
      flat: Flat.fromJson(json['flat'] as Map<String, dynamic>),
    );
  }

  final int residencyId;
  final String residentType;
  final bool isPrimary;
  final Flat flat;
}

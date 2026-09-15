import '../../../core/models/flat.dart';

class Visitor {
  Visitor({
    required this.id,
    required this.visitorName,
    this.visitorPhone,
    required this.purpose,
    this.vehicleNumber,
    required this.status,
    this.checkInAt,
    this.checkOutAt,
    this.expectedAt,
    this.passCode,
    this.notes,
    this.photoUrl,
    this.flat,
  });

  factory Visitor.fromJson(Map<String, dynamic> json) {
    final flatJson = json['flat'] as Map<String, dynamic>?;

    return Visitor(
      id: json['id'] as int,
      visitorName: json['visitor_name'] as String,
      visitorPhone: json['visitor_phone'] as String?,
      purpose: json['purpose'] as String,
      vehicleNumber: json['vehicle_number'] as String?,
      status: json['status'] as String,
      checkInAt: json['check_in_at'] as String?,
      checkOutAt: json['check_out_at'] as String?,
      expectedAt: json['expected_at'] as String?,
      passCode: json['pass_code'] as String?,
      notes: json['notes'] as String?,
      photoUrl: json['photo_url'] as String?,
      flat: flatJson != null ? Flat.fromJson(flatJson) : null,
    );
  }

  final int id;
  final String visitorName;
  final String? visitorPhone;
  final String purpose;
  final String? vehicleNumber;
  final String status;
  final String? checkInAt;
  final String? checkOutAt;
  final String? expectedAt;
  final String? passCode;
  final String? notes;
  // Set only for a gate-app walk-in check-in with a photo attached
  // (Api\V1\Guard\VisitorController::store) — camera-only on the app side.
  final String? photoUrl;
  // Only present on the gate-security app's society-wide list (a resident's
  // own visitor list doesn't need it — they already know it's their flat).
  final Flat? flat;

  static const purposes = ['guest', 'delivery', 'cab', 'service', 'other'];

  bool get isPending => status == 'pending';
  bool get isCheckedIn => status == 'checked_in';
}

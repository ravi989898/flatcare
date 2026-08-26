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
  });

  factory Visitor.fromJson(Map<String, dynamic> json) {
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

  static const purposes = ['guest', 'delivery', 'cab', 'service', 'other'];

  bool get isPending => status == 'pending';
}

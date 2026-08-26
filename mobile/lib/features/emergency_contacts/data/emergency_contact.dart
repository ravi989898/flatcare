class EmergencyContact {
  EmergencyContact({
    required this.id,
    required this.label,
    required this.phone,
    required this.type,
    this.availability,
  });

  factory EmergencyContact.fromJson(Map<String, dynamic> json) {
    return EmergencyContact(
      id: json['id'] as int,
      label: json['label'] as String,
      phone: json['phone'] as String,
      type: json['type'] as String,
      availability: json['availability'] as String?,
    );
  }

  final int id;
  final String label;
  final String phone;
  final String type;
  final String? availability;
}

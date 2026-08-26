class FamilyMember {
  FamilyMember({
    required this.id,
    required this.name,
    required this.relation,
    this.phone,
    this.dateOfBirth,
    this.occupation,
    this.medicalInfo,
    required this.status,
  });

  factory FamilyMember.fromJson(Map<String, dynamic> json) {
    return FamilyMember(
      id: json['id'] as int,
      name: json['name'] as String,
      relation: json['relation'] as String,
      phone: json['phone'] as String?,
      dateOfBirth: json['date_of_birth'] as String?,
      occupation: json['occupation'] as String?,
      medicalInfo: json['medical_info'] as String?,
      status: json['status'] as String? ?? 'active',
    );
  }

  final int id;
  final String name;
  final String relation;
  final String? phone;
  final String? dateOfBirth;
  final String? occupation;
  final String? medicalInfo;
  final String status;
}

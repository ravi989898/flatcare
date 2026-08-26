import '../../../core/models/flat.dart';

class DirectoryEntry {
  DirectoryEntry({
    required this.residencyId,
    required this.residentType,
    required this.isPrimary,
    required this.userId,
    this.name,
    this.phone,
    this.email,
    required this.flat,
  });

  factory DirectoryEntry.fromJson(Map<String, dynamic> json) {
    return DirectoryEntry(
      residencyId: json['residency_id'] as int,
      residentType: json['resident_type'] as String,
      isPrimary: json['is_primary'] as bool? ?? false,
      userId: json['user_id'] as int,
      name: json['name'] as String?,
      phone: json['phone'] as String?,
      email: json['email'] as String?,
      flat: Flat.fromJson(json['flat'] as Map<String, dynamic>),
    );
  }

  final int residencyId;
  final String residentType;
  final bool isPrimary;
  final int userId;
  final String? name;
  final String? phone;
  final String? email;
  final Flat flat;
}

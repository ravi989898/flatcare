import 'residency.dart';

class UserProfile {
  UserProfile({
    required this.id,
    required this.name,
    required this.email,
    this.phone,
    this.profilePhotoUrl,
    this.gender,
    this.dateOfBirth,
    this.address,
    this.city,
    this.state,
    this.country,
    this.postalCode,
    required this.status,
    this.roles = const [],
    this.flats = const [],
  });

  factory UserProfile.fromJson(Map<String, dynamic> json) {
    return UserProfile(
      id: json['id'] as int,
      name: json['name'] as String,
      email: json['email'] as String,
      phone: json['phone'] as String?,
      profilePhotoUrl: json['profile_photo_url'] as String?,
      gender: json['gender'] as String?,
      dateOfBirth: json['date_of_birth'] as String?,
      address: json['address'] as String?,
      city: json['city'] as String?,
      state: json['state'] as String?,
      country: json['country'] as String?,
      postalCode: json['postal_code'] as String?,
      status: json['status'] as String? ?? 'active',
      roles: (json['roles'] as List?)?.map((r) => r.toString()).toList() ?? const [],
      flats: (json['flats'] as List?)
              ?.map((f) => Residency.fromJson(f as Map<String, dynamic>))
              .toList() ??
          const [],
    );
  }

  final int id;
  final String name;
  final String email;
  final String? phone;
  final String? profilePhotoUrl;
  final String? gender;
  final String? dateOfBirth;
  final String? address;
  final String? city;
  final String? state;
  final String? country;
  final String? postalCode;
  final String status;
  final List<String> roles;
  final List<Residency> flats;

  Residency? get primaryResidency =>
      flats.isEmpty ? null : flats.firstWhere((f) => f.isPrimary, orElse: () => flats.first);
}

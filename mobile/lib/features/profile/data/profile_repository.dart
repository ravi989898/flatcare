import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client.dart';
import '../../../core/models/user_profile.dart';
import '../../../core/providers/core_providers.dart';
import 'family_member.dart';
import 'vehicle.dart';

class ProfileRepository {
  ProfileRepository(this._client);

  final ApiClient _client;

  Future<UserProfile> show() async {
    final response = await _client.get('/profile');
    return UserProfile.fromJson(response['data'] as Map<String, dynamic>);
  }

  Future<UserProfile> update(Map<String, dynamic> fields) async {
    final response = await _client.put('/profile', data: fields);
    return UserProfile.fromJson(response['data'] as Map<String, dynamic>);
  }

  Future<List<FamilyMember>> familyMembers() async {
    final response = await _client.get('/profile/family-members');
    return (response['data'] as List)
        .map((item) => FamilyMember.fromJson(item as Map<String, dynamic>))
        .toList();
  }

  Future<void> addFamilyMember(Map<String, dynamic> fields) =>
      _client.post('/profile/family-members', data: fields);

  Future<void> updateFamilyMember(int id, Map<String, dynamic> fields) =>
      _client.put('/profile/family-members/$id', data: fields);

  Future<void> deleteFamilyMember(int id) => _client.delete('/profile/family-members/$id');

  Future<List<Vehicle>> vehicles() async {
    final response = await _client.get('/profile/vehicles');
    return (response['data'] as List).map((item) => Vehicle.fromJson(item as Map<String, dynamic>)).toList();
  }

  Future<void> addVehicle(Map<String, dynamic> fields) => _client.post('/profile/vehicles', data: fields);

  Future<void> updateVehicle(int id, Map<String, dynamic> fields) =>
      _client.put('/profile/vehicles/$id', data: fields);

  Future<void> deleteVehicle(int id) => _client.delete('/profile/vehicles/$id');
}

final profileRepositoryProvider = Provider<ProfileRepository>((ref) {
  return ProfileRepository(ref.watch(apiClientProvider));
});

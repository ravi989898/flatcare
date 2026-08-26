import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client.dart';
import '../../../core/providers/core_providers.dart';
import 'emergency_contact.dart';

class EmergencyContactRepository {
  EmergencyContactRepository(this._client);

  final ApiClient _client;

  Future<List<EmergencyContact>> list() async {
    final response = await _client.get('/emergency-contacts');

    return (response['data'] as List).map((item) => EmergencyContact.fromJson(item as Map<String, dynamic>)).toList();
  }
}

final emergencyContactRepositoryProvider = Provider<EmergencyContactRepository>((ref) {
  return EmergencyContactRepository(ref.watch(apiClientProvider));
});

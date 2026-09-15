import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client.dart';
import '../../../core/providers/core_providers.dart';
import 'guard_vehicle.dart';

/// Society-wide, read-only vehicle lookup for the gate app
/// (Api\V1\Guard\VehicleController) — so a guard can check whether a
/// vehicle at the gate is actually registered to a resident.
class GuardVehicleRepository {
  GuardVehicleRepository(this._client);

  final ApiClient _client;

  Future<List<GuardVehicle>> search(String query) async {
    final response = await _client.get('/guard/vehicles', query: {
      if (query.isNotEmpty) 'search': query,
    });

    return (response['data'] as List).map((item) => GuardVehicle.fromJson(item as Map<String, dynamic>)).toList();
  }
}

final guardVehicleRepositoryProvider = Provider<GuardVehicleRepository>((ref) {
  return GuardVehicleRepository(ref.watch(apiClientProvider));
});

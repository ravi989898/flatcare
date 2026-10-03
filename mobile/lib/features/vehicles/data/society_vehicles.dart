import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client.dart';
import '../../../core/providers/core_providers.dart';

class VehicleBlock {
  const VehicleBlock({required this.id, required this.name});

  final int id;
  final String name;
}

class VehicleTypeCount {
  const VehicleTypeCount({required this.type, required this.count});

  final String type;
  final int count;
}

class ResidentVehicle {
  const ResidentVehicle({required this.id, required this.type, required this.registrationNumber});

  final int id;
  final String type;
  final String registrationNumber;
}

/// One card on the Vehicles screen: a resident of the selected block and
/// every vehicle registered to them.
class VehicleOwner {
  const VehicleOwner({
    required this.userId,
    required this.name,
    required this.phone,
    required this.flatNumber,
    required this.blockName,
    required this.residentType,
    required this.vehicles,
  });

  final int userId;
  final String name;
  final String? phone;
  final String flatNumber;
  final String? blockName;
  final String residentType;
  final List<ResidentVehicle> vehicles;
}

class SocietyVehicles {
  const SocietyVehicles({required this.blocks, required this.blockId, required this.counts, required this.residents});

  final List<VehicleBlock> blocks;
  final int? blockId;
  final List<VehicleTypeCount> counts;
  final List<VehicleOwner> residents;

  factory SocietyVehicles.fromJson(Map<String, dynamic> json) => SocietyVehicles(
        blocks: [
          for (final b in json['blocks'] as List) VehicleBlock(id: b['id'] as int, name: b['name'] as String),
        ],
        blockId: json['block_id'] as int?,
        counts: [
          for (final c in json['counts'] as List) VehicleTypeCount(type: c['type'] as String, count: c['count'] as int),
        ],
        residents: [
          for (final r in json['residents'] as List)
            VehicleOwner(
              userId: r['user_id'] as int,
              name: (r['name'] as String?) ?? 'Resident',
              phone: r['phone'] as String?,
              flatNumber: r['flat_number'] as String,
              blockName: r['block_name'] as String?,
              residentType: (r['resident_type'] as String?) ?? '',
              vehicles: [
                for (final v in r['vehicles'] as List)
                  ResidentVehicle(
                    id: v['id'] as int,
                    type: (v['vehicle_type'] as String?) ?? '',
                    registrationNumber: v['registration_number'] as String,
                  ),
              ],
            ),
        ],
      );
}

class SocietyVehicleRepository {
  SocietyVehicleRepository(this._client);

  final ApiClient _client;

  Future<SocietyVehicles> list({int? blockId, String search = ''}) async {
    final response = await _client.get('/society-vehicles', query: {
      'block_id': ?blockId,
      if (search.isNotEmpty) 'search': search,
    });
    return SocietyVehicles.fromJson(response['data'] as Map<String, dynamic>);
  }
}

final societyVehicleRepositoryProvider = Provider<SocietyVehicleRepository>((ref) {
  return SocietyVehicleRepository(ref.watch(apiClientProvider));
});

/// null = let the server pick the first block.
final vehicleBlockProvider = StateProvider.autoDispose<int?>((ref) => null);
final vehicleSearchProvider = StateProvider.autoDispose<String>((ref) => '');

final societyVehiclesProvider = FutureProvider.autoDispose<SocietyVehicles>((ref) {
  return ref.watch(societyVehicleRepositoryProvider).list(
        blockId: ref.watch(vehicleBlockProvider),
        search: ref.watch(vehicleSearchProvider),
      );
});

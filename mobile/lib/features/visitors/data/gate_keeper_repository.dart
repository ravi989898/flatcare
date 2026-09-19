import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client.dart';
import '../../../core/providers/core_providers.dart';

/// A security guard currently on duty, as returned by GET /security-guard.
class GateKeeper {
  GateKeeper({required this.id, required this.name, this.phone, required this.shift, this.photoUrl});

  factory GateKeeper.fromJson(Map<String, dynamic> json) {
    return GateKeeper(
      id: json['id'] as int,
      name: json['name'] as String,
      phone: json['phone'] as String?,
      shift: json['shift'] as String? ?? '',
      photoUrl: json['photo_url'] as String?,
    );
  }

  final int id;
  final String name;
  final String? phone;
  final String shift;
  final String? photoUrl;
}

/// The guard holding each shift right now — null when that shift is vacant.
class GateKeepers {
  GateKeepers({this.day, this.night});

  final GateKeeper? day;
  final GateKeeper? night;

  bool get isEmpty => day == null && night == null;
}

class GateKeeperRepository {
  GateKeeperRepository(this._client);

  final ApiClient _client;

  Future<GateKeepers> fetch() async {
    final response = await _client.get('/security-guard');
    final data = response['data'] as Map<String, dynamic>;

    GateKeeper? parse(String key) {
      final json = data[key];
      return json is Map<String, dynamic> ? GateKeeper.fromJson(json) : null;
    }

    return GateKeepers(day: parse('day'), night: parse('night'));
  }
}

final gateKeeperRepositoryProvider = Provider<GateKeeperRepository>((ref) {
  return GateKeeperRepository(ref.watch(apiClientProvider));
});

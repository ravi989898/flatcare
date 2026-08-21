import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client.dart';
import '../../../core/providers/core_providers.dart';
import 'maintenance_request.dart';

class MaintenanceRequestRepository {
  MaintenanceRequestRepository(this._client);

  final ApiClient _client;

  Future<List<MaintenanceRequest>> list({String? status}) async {
    final response = await _client.get('/maintenance-requests', query: {
      if (status != null) 'status': status,
    });

    return (response['data'] as List)
        .map((item) => MaintenanceRequest.fromJson(item as Map<String, dynamic>))
        .toList();
  }

  Future<MaintenanceRequest> show(int id) async {
    final response = await _client.get('/maintenance-requests/$id');
    return MaintenanceRequest.fromJson(response['data'] as Map<String, dynamic>);
  }

  Future<MaintenanceRequest> create({
    required int flatId,
    required String category,
    required String title,
    required String description,
    required String priority,
  }) async {
    final response = await _client.post('/maintenance-requests', data: {
      'flat_id': flatId,
      'category': category,
      'title': title,
      'description': description,
      'priority': priority,
    });

    return MaintenanceRequest.fromJson(response['data'] as Map<String, dynamic>);
  }
}

final maintenanceRequestRepositoryProvider = Provider<MaintenanceRequestRepository>((ref) {
  return MaintenanceRequestRepository(ref.watch(apiClientProvider));
});

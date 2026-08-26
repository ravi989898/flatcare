import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client.dart';
import '../../../core/providers/core_providers.dart';
import 'complaint.dart';

class ComplaintRepository {
  ComplaintRepository(this._client);

  final ApiClient _client;

  Future<List<Complaint>> list({String? status}) async {
    final response = await _client.get('/complaints', query: {
      if (status != null) 'status': status,
    });

    return (response['data'] as List)
        .map((item) => Complaint.fromJson(item as Map<String, dynamic>))
        .toList();
  }

  Future<Complaint> show(int id) async {
    final response = await _client.get('/complaints/$id');
    return Complaint.fromJson(response['data'] as Map<String, dynamic>);
  }

  Future<Complaint> create({
    required int flatId,
    required String category,
    required String subject,
    required String description,
    String? against,
    required String priority,
  }) async {
    final response = await _client.post('/complaints', data: {
      'flat_id': flatId,
      'category': category,
      'subject': subject,
      'description': description,
      if (against != null && against.isNotEmpty) 'against': against,
      'priority': priority,
    });

    return Complaint.fromJson(response['data'] as Map<String, dynamic>);
  }
}

final complaintRepositoryProvider = Provider<ComplaintRepository>((ref) {
  return ComplaintRepository(ref.watch(apiClientProvider));
});

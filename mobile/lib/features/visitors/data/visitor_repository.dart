import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client.dart';
import '../../../core/providers/core_providers.dart';
import 'visitor.dart';

class VisitorRepository {
  VisitorRepository(this._client);

  final ApiClient _client;

  Future<List<Visitor>> list({String? status}) async {
    final response = await _client.get('/visitors', query: {
      if (status != null) 'status': status,
    });

    return (response['data'] as List).map((item) => Visitor.fromJson(item as Map<String, dynamic>)).toList();
  }

  /// "Invite Visitor" / "Gate Pass" — a resident pre-approving an expected
  /// visitor. Returns the created row, including its pass_code, so the
  /// caller can show a shareable Gate Pass right after creating it.
  Future<Visitor> invite({
    required int flatId,
    required String visitorName,
    String? visitorPhone,
    required String purpose,
    String? vehicleNumber,
    DateTime? expectedAt,
    String? notes,
  }) async {
    final response = await _client.post('/visitors', data: {
      'flat_id': flatId,
      'visitor_name': visitorName,
      if (visitorPhone != null && visitorPhone.isNotEmpty) 'visitor_phone': visitorPhone,
      'purpose': purpose,
      if (vehicleNumber != null && vehicleNumber.isNotEmpty) 'vehicle_number': vehicleNumber,
      if (expectedAt != null) 'expected_at': expectedAt.toIso8601String(),
      if (notes != null && notes.isNotEmpty) 'notes': notes,
    });

    return Visitor.fromJson(response['data'] as Map<String, dynamic>);
  }
}

final visitorRepositoryProvider = Provider<VisitorRepository>((ref) {
  return VisitorRepository(ref.watch(apiClientProvider));
});

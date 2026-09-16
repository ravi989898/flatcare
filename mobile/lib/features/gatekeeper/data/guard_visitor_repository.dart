import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client.dart';
import '../../../core/providers/core_providers.dart';
import '../../visitors/data/visitor.dart';

/// The gate register, from the guard app (Api\V1\Guard\VisitorController) —
/// society-wide, unlike features/visitors/data/visitor_repository.dart
/// which is scoped to the resident's own flat(s).
class GuardVisitorRepository {
  GuardVisitorRepository(this._client);

  final ApiClient _client;

  Future<List<Visitor>> list({String? status, String? search}) async {
    final response = await _client.get('/guard/visitors', query: {
      if (status != null) 'status': status,
      if (search != null && search.isNotEmpty) 'search': search,
    });

    return (response['data'] as List).map((item) => Visitor.fromJson(item as Map<String, dynamic>)).toList();
  }

  /// Walk-in check-in — a visitor who wasn't pre-invited by a resident.
  /// `photoPath` (a local file from the device camera — see
  /// GatekeeperVisitorCheckinScreen) is optional and, when given, sent as
  /// multipart alongside the other fields. When `requiresApproval` is true,
  /// the visitor lands `pending` instead of `checked_in` and the resident
  /// gets an Approve/Reject notification instead of an arrival notice.
  Future<Visitor> checkInWalkIn({
    required int flatId,
    required String visitorName,
    String? visitorPhone,
    required String purpose,
    String? vehicleNumber,
    String? notes,
    String? photoPath,
    bool requiresApproval = false,
  }) async {
    final response = await _client.postMultipart(
      '/guard/visitors',
      filePath: photoPath,
      fields: {
        'flat_id': flatId,
        'visitor_name': visitorName,
        if (visitorPhone != null && visitorPhone.isNotEmpty) 'visitor_phone': visitorPhone,
        'purpose': purpose,
        if (vehicleNumber != null && vehicleNumber.isNotEmpty) 'vehicle_number': vehicleNumber,
        if (notes != null && notes.isNotEmpty) 'notes': notes,
        if (requiresApproval) 'requires_approval': true,
      },
    );

    return Visitor.fromJson(response['data'] as Map<String, dynamic>);
  }

  /// A resident-pre-invited (pending) visitor has just arrived.
  Future<Visitor> checkIn(int visitorId) async {
    final response = await _client.post('/guard/visitors/$visitorId/check-in');

    return Visitor.fromJson(response['data'] as Map<String, dynamic>);
  }

  Future<Visitor> checkOut(int visitorId) async {
    final response = await _client.post('/guard/visitors/$visitorId/check-out');

    return Visitor.fromJson(response['data'] as Map<String, dynamic>);
  }
}

final guardVisitorRepositoryProvider = Provider<GuardVisitorRepository>((ref) {
  return GuardVisitorRepository(ref.watch(apiClientProvider));
});

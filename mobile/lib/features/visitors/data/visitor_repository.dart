import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/api/api_client.dart';
import '../../../core/providers/core_providers.dart';
import 'visitor.dart';

/// Everything the My Visitors list can be narrowed by. A record so it can be
/// the key of a Riverpod `family` (records compare by value).
typedef VisitorQuery = ({String? status, String? search, DateTime? from, DateTime? to, String? kind});

const noVisitorFilter = (status: null, search: null, from: null, to: null, kind: null);

final _apiDate = DateFormat('yyyy-MM-dd');

class VisitorRepository {
  VisitorRepository(this._client);

  final ApiClient _client;

  Future<List<Visitor>> list(VisitorQuery query) async {
    final response = await _client.get('/visitors', query: {
      if (query.status != null) 'status': query.status,
      if (query.kind != null) 'kind': query.kind,
      if (query.search != null && query.search!.trim().isNotEmpty) 'search': query.search!.trim(),
      if (query.from != null) 'from': _apiDate.format(query.from!),
      if (query.to != null) 'to': _apiDate.format(query.to!),
    });

    return (response['data'] as List).map((item) => Visitor.fromJson(item as Map<String, dynamic>)).toList();
  }

  /// "Gate Pass" and "Pre-Approval" — a resident pre-approving an expected
  /// visitor. `kind` records which form made it ('gate_pass' has a From/To
  /// window, 'pre_approval' doesn't). Returns the created row, including its
  /// pass_code, so the caller can show a shareable Gate Pass right after.
  Future<Visitor> invite({
    required int flatId,
    required String visitorName,
    required String visitorPhone,
    required String purpose,
    String kind = 'gate_pass',
    String? visitorEmail,
    DateTime? from,
    DateTime? to,
    String? notes,
    String? photoPath,
  }) async {
    final response = await _client.postMultipart(
      '/visitors',
      filePath: photoPath,
      fields: {
        'flat_id': flatId,
        'visitor_name': visitorName,
        'visitor_phone': visitorPhone,
        'purpose': purpose,
        'entry_kind': kind,
        if (visitorEmail != null && visitorEmail.isNotEmpty) 'visitor_email': visitorEmail,
        if (from != null) 'expected_at': DateTime(from.year, from.month, from.day).toIso8601String(),
        if (to != null) 'valid_until': DateTime(to.year, to.month, to.day, 23, 59, 59).toIso8601String(),
        if (notes != null && notes.isNotEmpty) 'notes': notes,
      },
    );

    return Visitor.fromJson(response['data'] as Map<String, dynamic>);
  }

  /// Withdraw a pass / pre-approval that hasn't been used yet.
  Future<void> cancel(int visitorId) async {
    await _client.delete('/visitors/$visitorId');
  }

  /// Approve/reject a guard-raised entry request (Visitor.awaitingApproval)
  /// — approving checks the visitor in immediately and notifies the guard
  /// back so they know to let them through.
  Future<Visitor> approve(int visitorId) async {
    final response = await _client.post('/visitors/$visitorId/approve');

    return Visitor.fromJson(response['data'] as Map<String, dynamic>);
  }

  Future<Visitor> reject(int visitorId) async {
    final response = await _client.post('/visitors/$visitorId/reject');

    return Visitor.fromJson(response['data'] as Map<String, dynamic>);
  }
}

final visitorRepositoryProvider = Provider<VisitorRepository>((ref) {
  return VisitorRepository(ref.watch(apiClientProvider));
});

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client.dart';
import '../../../core/providers/core_providers.dart';
import '../../visitors/data/visitor.dart';
import 'guard_summary.dart';

class GuardVisitorPage {
  GuardVisitorPage({required this.items, required this.page, required this.lastPage, this.total});

  final List<Visitor> items;
  final int page;
  final int lastPage;
  final int? total;

  bool get hasMore => page < lastPage;
}

/// The scanner's verdict on a gate pass (Guard\VisitorController::verifyPass):
/// `result` is valid / upcoming / inside / expired / used / cancelled /
/// invalid, `message` says what to do, and `visitor` is the pass (null when
/// no pass matches the code).
class PassCheck {
  PassCheck({required this.result, required this.message, this.visitor});

  factory PassCheck.fromJson(Map<String, dynamic> json) {
    final visitor = json['visitor'] as Map<String, dynamic>?;
    return PassCheck(
      result: json['result'] as String? ?? 'invalid',
      message: json['message'] as String? ?? '',
      visitor: visitor != null ? Visitor.fromJson(visitor) : null,
    );
  }

  final String result;
  final String message;
  final Visitor? visitor;
}

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

  /// One page of the gate register. [kind] is `requests` (awaiting the
  /// resident) or `passes` (usable gate passes); [date] is `yyyy-MM-dd`.
  Future<GuardVisitorPage> page({
    String? status,
    String? kind,
    String? date,
    String? search,
    int page = 1,
  }) async {
    final response = await _client.get('/guard/visitors', query: {
      if (status != null) 'status': status,
      if (kind != null) 'kind': kind,
      if (date != null) 'date': date,
      if (search != null && search.isNotEmpty) 'search': search,
      'page': page,
    });
    final pagination = response['pagination'] as Map<String, dynamic>?;

    return GuardVisitorPage(
      items: (response['data'] as List).map((item) => Visitor.fromJson(item as Map<String, dynamic>)).toList(),
      page: pagination?['current_page'] as int? ?? page,
      lastPage: pagination?['last_page'] as int? ?? page,
      total: pagination?['total'] as int?,
    );
  }

  /// Checks a scanned QR (or a pass number typed in by hand).
  Future<PassCheck> verifyPass(String code) async {
    final response = await _client.post('/guard/visitors/verify-pass', data: {'code': code});
    return PassCheck.fromJson(response['data'] as Map<String, dynamic>);
  }

  Future<GuardSummary> summary() async {
    final response = await _client.get('/guard/summary');
    return GuardSummary.fromJson(response['data'] as Map<String, dynamic>);
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
    bool requiresApproval = true,
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
        // Always sent: the server defaults to "needs approval" when it is missing.
        'requires_approval': requiresApproval,
      },
    );

    return Visitor.fromJson(response['data'] as Map<String, dynamic>);
  }

  /// Let an APPROVED visitor (or one with a resident's pre-approved pass) in — ENTERED.
  Future<Visitor> checkIn(int visitorId) async {
    final response = await _client.post('/guard/visitors/$visitorId/entry');

    return Visitor.fromJson(response['data'] as Map<String, dynamic>);
  }

  /// The visitor has left — EXITED.
  Future<Visitor> checkOut(int visitorId) async {
    final response = await _client.post('/guard/visitors/$visitorId/exit');

    return Visitor.fromJson(response['data'] as Map<String, dynamic>);
  }
}

final guardVisitorRepositoryProvider = Provider<GuardVisitorRepository>((ref) {
  return GuardVisitorRepository(ref.watch(apiClientProvider));
});

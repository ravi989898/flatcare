import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client.dart';
import '../../../core/providers/core_providers.dart';
import 'visitor_settings.dart';

class VisitorSettingsRepository {
  VisitorSettingsRepository(this._client);

  final ApiClient _client;

  Future<VisitorSettings> fetch() async {
    final response = await _client.get('/visitor-settings');

    return VisitorSettings.fromJson(response['data'] as Map<String, dynamic>);
  }

  Future<VisitorSettings> save({
    required int flatId,
    required bool guestApprovalRequired,
    required bool houseClosed,
  }) async {
    final response = await _client.put('/visitor-settings', data: {
      'flat_id': flatId,
      'guest_approval_required': guestApprovalRequired,
      'house_closed': houseClosed,
    });

    return VisitorSettings.fromJson(response['data'] as Map<String, dynamic>);
  }
}

final visitorSettingsRepositoryProvider = Provider<VisitorSettingsRepository>((ref) {
  return VisitorSettingsRepository(ref.watch(apiClientProvider));
});

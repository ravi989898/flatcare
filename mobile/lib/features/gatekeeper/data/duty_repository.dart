import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client.dart';
import '../../../core/providers/core_providers.dart';
import 'duty_status.dart';

/// A guard's own duty shift (Api\V1\Guard\DutyController) — start/end duty
/// from the app instead of only a Society Admin assigning it from the web
/// portal.
class DutyRepository {
  DutyRepository(this._client);

  final ApiClient _client;

  Future<DutyStatus> status() async {
    final response = await _client.get('/guard/duty');

    return DutyStatus.fromJson(response['data'] as Map<String, dynamic>);
  }

  Future<void> start(String shift) async {
    await _client.post('/guard/duty/start', data: {'shift': shift});
  }

  Future<void> end() async {
    await _client.post('/guard/duty/end');
  }
}

final dutyRepositoryProvider = Provider<DutyRepository>((ref) {
  return DutyRepository(ref.watch(apiClientProvider));
});

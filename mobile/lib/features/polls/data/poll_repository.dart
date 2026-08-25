import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client.dart';
import '../../../core/providers/core_providers.dart';
import 'poll.dart';

class PollRepository {
  PollRepository(this._client);

  final ApiClient _client;

  Future<List<Poll>> list() async {
    final response = await _client.get('/polls');

    return (response['data'] as List).map((item) => Poll.fromJson(item as Map<String, dynamic>)).toList();
  }

  Future<Poll> vote(int pollId, int optionId) async {
    final response = await _client.post('/polls/$pollId/vote', data: {'poll_option_id': optionId});

    return Poll.fromJson(response['data'] as Map<String, dynamic>);
  }
}

final pollRepositoryProvider = Provider<PollRepository>((ref) {
  return PollRepository(ref.watch(apiClientProvider));
});

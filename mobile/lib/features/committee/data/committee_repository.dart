import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client.dart';
import '../../../core/providers/core_providers.dart';
import 'committee_member.dart';

class CommitteeRepository {
  CommitteeRepository(this._client);

  final ApiClient _client;

  Future<List<CommitteeMember>> list() async {
    final response = await _client.get('/committee-members');

    return (response['data'] as List).map((item) => CommitteeMember.fromJson(item as Map<String, dynamic>)).toList();
  }
}

final committeeRepositoryProvider = Provider<CommitteeRepository>((ref) {
  return CommitteeRepository(ref.watch(apiClientProvider));
});

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
}

final visitorRepositoryProvider = Provider<VisitorRepository>((ref) {
  return VisitorRepository(ref.watch(apiClientProvider));
});

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client.dart';
import '../../../core/providers/core_providers.dart';
import 'directory_entry.dart';

class DirectoryRepository {
  DirectoryRepository(this._client);

  final ApiClient _client;

  Future<List<DirectoryEntry>> list({String? search}) async {
    final response = await _client.get('/directory', query: {
      if (search != null && search.isNotEmpty) 'search': search,
    });

    return (response['data'] as List)
        .map((item) => DirectoryEntry.fromJson(item as Map<String, dynamic>))
        .toList();
  }
}

final directoryRepositoryProvider = Provider<DirectoryRepository>((ref) {
  return DirectoryRepository(ref.watch(apiClientProvider));
});

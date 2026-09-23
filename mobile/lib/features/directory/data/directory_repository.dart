import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client.dart';
import '../../../core/providers/core_providers.dart';
import 'directory_entry.dart';

/// One page of /directory results (the endpoint pages 20 at a time).
class DirectoryPage {
  DirectoryPage({required this.items, required this.page, required this.lastPage, this.total});

  final List<DirectoryEntry> items;
  final int page;
  final int lastPage;
  final int? total;

  bool get hasMore => page < lastPage;
}

class DirectoryRepository {
  DirectoryRepository(this._client);

  final ApiClient _client;

  /// [residentType] is `owner`, `tenant` or `occupant` (null = everyone).
  Future<DirectoryPage> list({String? search, String? residentType, int page = 1}) async {
    final response = await _client.get('/directory', query: {
      if (search != null && search.isNotEmpty) 'search': search,
      if (residentType != null) 'resident_type': residentType,
      'page': page,
    });

    final pagination = response['pagination'] as Map<String, dynamic>?;

    return DirectoryPage(
      items: (response['data'] as List)
          .map((item) => DirectoryEntry.fromJson(item as Map<String, dynamic>))
          .toList(),
      page: pagination?['current_page'] as int? ?? page,
      lastPage: pagination?['last_page'] as int? ?? page,
      total: pagination?['total'] as int?,
    );
  }
}

final directoryRepositoryProvider = Provider<DirectoryRepository>((ref) {
  return DirectoryRepository(ref.watch(apiClientProvider));
});

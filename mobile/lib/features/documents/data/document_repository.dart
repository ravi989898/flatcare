import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client.dart';
import '../../../core/providers/core_providers.dart';
import 'document.dart';

class DocumentRepository {
  DocumentRepository(this._client);

  final ApiClient _client;

  Future<List<AppDocument>> list({String? category}) async {
    final response = await _client.get('/documents', query: {
      if (category != null) 'category': category,
    });

    return (response['data'] as List).map((item) => AppDocument.fromJson(item as Map<String, dynamic>)).toList();
  }
}

final documentRepositoryProvider = Provider<DocumentRepository>((ref) {
  return DocumentRepository(ref.watch(apiClientProvider));
});

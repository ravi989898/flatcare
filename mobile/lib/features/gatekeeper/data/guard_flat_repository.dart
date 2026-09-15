import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client.dart';
import '../../../core/models/flat.dart';
import '../../../core/providers/core_providers.dart';

/// Society-wide flat lookup for the walk-in check-in form's flat picker
/// (Api\V1\Guard\FlatController) — a resident only ever needs their own
/// flat(s), already carried on their profile, so nothing like this exists
/// for the resident side.
class GuardFlatRepository {
  GuardFlatRepository(this._client);

  final ApiClient _client;

  Future<List<Flat>> search(String query) async {
    final response = await _client.get('/guard/flats', query: {
      if (query.isNotEmpty) 'search': query,
    });

    return (response['data'] as List).map((item) => Flat.fromJson(item as Map<String, dynamic>)).toList();
  }
}

final guardFlatRepositoryProvider = Provider<GuardFlatRepository>((ref) {
  return GuardFlatRepository(ref.watch(apiClientProvider));
});

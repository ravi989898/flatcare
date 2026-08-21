import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client.dart';
import '../../../core/providers/core_providers.dart';
import 'bill.dart';

class BillRepository {
  BillRepository(this._client);

  final ApiClient _client;

  Future<List<Bill>> list({String? status}) async {
    final response = await _client.get('/bills', query: {
      if (status != null) 'status': status,
    });

    return (response['data'] as List).map((item) => Bill.fromJson(item as Map<String, dynamic>)).toList();
  }

  Future<Bill> show(int id) async {
    final response = await _client.get('/bills/$id');
    return Bill.fromJson(response['data'] as Map<String, dynamic>);
  }
}

final billRepositoryProvider = Provider<BillRepository>((ref) {
  return BillRepository(ref.watch(apiClientProvider));
});

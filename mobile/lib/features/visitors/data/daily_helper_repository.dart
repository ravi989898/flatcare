import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client.dart';
import '../../../core/providers/core_providers.dart';
import 'daily_helper.dart';

class DailyHelperRepository {
  DailyHelperRepository(this._client);

  final ApiClient _client;

  Future<List<DailyHelper>> list() async {
    final response = await _client.get('/daily-helpers');

    return (response['data'] as List).map((item) => DailyHelper.fromJson(item as Map<String, dynamic>)).toList();
  }

  Future<DailyHelper> add({
    required int flatId,
    required String name,
    required String phone,
    required String helperType,
    String? photoPath,
  }) async {
    final response = await _client.postMultipart(
      '/daily-helpers',
      filePath: photoPath,
      fields: {
        'flat_id': flatId,
        'name': name,
        'phone': phone,
        'helper_type': helperType,
      },
    );

    return DailyHelper.fromJson(response['data'] as Map<String, dynamic>);
  }

  Future<void> remove(int id) async {
    await _client.delete('/daily-helpers/$id');
  }
}

final dailyHelperRepositoryProvider = Provider<DailyHelperRepository>((ref) {
  return DailyHelperRepository(ref.watch(apiClientProvider));
});

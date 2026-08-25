import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client.dart';
import '../../../core/providers/core_providers.dart';
import 'service_provider.dart';

class ServiceProviderRepository {
  ServiceProviderRepository(this._client);

  final ApiClient _client;

  Future<List<ServiceProvider>> list() async {
    final response = await _client.get('/service-providers');

    return (response['data'] as List).map((item) => ServiceProvider.fromJson(item as Map<String, dynamic>)).toList();
  }
}

final serviceProviderRepositoryProvider = Provider<ServiceProviderRepository>((ref) {
  return ServiceProviderRepository(ref.watch(apiClientProvider));
});

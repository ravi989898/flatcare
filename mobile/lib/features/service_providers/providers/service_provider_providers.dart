import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../data/service_provider.dart';
import '../data/service_provider_repository.dart';

final serviceProviderListProvider = FutureProvider.autoDispose<List<ServiceProvider>>((ref) {
  return ref.watch(serviceProviderRepositoryProvider).list();
});

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client.dart';
import '../../../core/providers/core_providers.dart';
import 'event.dart';

class EventRepository {
  EventRepository(this._client);

  final ApiClient _client;

  Future<List<Event>> list({String when = 'upcoming'}) async {
    final response = await _client.get('/events', query: {'when': when});
    return (response['data'] as List).map((item) => Event.fromJson(item as Map<String, dynamic>)).toList();
  }

  Future<Event> show(int id) async {
    final response = await _client.get('/events/$id');
    return Event.fromJson(response['data'] as Map<String, dynamic>);
  }
}

final eventRepositoryProvider = Provider<EventRepository>((ref) {
  return EventRepository(ref.watch(apiClientProvider));
});

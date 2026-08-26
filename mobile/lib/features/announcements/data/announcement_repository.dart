import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client.dart';
import '../../../core/providers/core_providers.dart';
import 'announcement.dart';

class AnnouncementRepository {
  AnnouncementRepository(this._client);

  final ApiClient _client;

  Future<List<Announcement>> list() async {
    final response = await _client.get('/announcements');
    return (response['data'] as List)
        .map((item) => Announcement.fromJson(item as Map<String, dynamic>))
        .toList();
  }

  Future<Announcement> show(int id) async {
    final response = await _client.get('/announcements/$id');
    return Announcement.fromJson(response['data'] as Map<String, dynamic>);
  }
}

final announcementRepositoryProvider = Provider<AnnouncementRepository>((ref) {
  return AnnouncementRepository(ref.watch(apiClientProvider));
});

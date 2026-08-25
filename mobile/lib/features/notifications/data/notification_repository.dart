import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client.dart';
import '../../../core/providers/core_providers.dart';
import 'app_notification.dart';

class NotificationRepository {
  NotificationRepository(this._client);

  final ApiClient _client;

  Future<List<AppNotification>> list() async {
    final response = await _client.get('/notifications');

    return (response['data'] as List).map((item) => AppNotification.fromJson(item as Map<String, dynamic>)).toList();
  }

  Future<int> unreadCount() async {
    final response = await _client.get('/notifications/unread-count');

    return (response['data'] as Map<String, dynamic>)['count'] as int;
  }

  Future<void> markRead(int id) async {
    await _client.post('/notifications/$id/read');
  }

  Future<void> markAllRead() async {
    await _client.post('/notifications/mark-all-read');
  }
}

final notificationRepositoryProvider = Provider<NotificationRepository>((ref) {
  return NotificationRepository(ref.watch(apiClientProvider));
});

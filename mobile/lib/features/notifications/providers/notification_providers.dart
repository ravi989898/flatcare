import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../data/app_notification.dart';
import '../data/notification_repository.dart';

final notificationListProvider = FutureProvider.autoDispose<List<AppNotification>>((ref) {
  return ref.watch(notificationRepositoryProvider).list();
});

final unreadNotificationCountProvider = FutureProvider.autoDispose<int>((ref) {
  return ref.watch(notificationRepositoryProvider).unreadCount();
});

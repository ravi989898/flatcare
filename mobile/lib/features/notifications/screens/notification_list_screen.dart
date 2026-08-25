import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/async_view.dart';
import '../data/app_notification.dart';
import '../data/notification_repository.dart';
import '../providers/notification_providers.dart';

class NotificationListScreen extends ConsumerWidget {
  const NotificationListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final notifications = ref.watch(notificationListProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Notifications'),
        actions: [
          TextButton(
            onPressed: () async {
              await ref.read(notificationRepositoryProvider).markAllRead();
              ref.invalidate(notificationListProvider);
              ref.invalidate(unreadNotificationCountProvider);
            },
            child: const Text('Mark all as read'),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(notificationListProvider.future),
        child: AsyncView<List<AppNotification>>(
          value: notifications,
          onRetry: () => ref.invalidate(notificationListProvider),
          builder: (context, items) {
            if (items.isEmpty) {
              return const EmptyState(message: 'No notifications yet.', icon: Icons.notifications_none);
            }

            return ListView.separated(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              itemCount: items.length,
              separatorBuilder: (_, __) => const SizedBox(height: 4),
              itemBuilder: (context, index) {
                final item = items[index];

                return Card(
                  color: item.isRead ? null : Theme.of(context).colorScheme.primaryContainer.withValues(alpha: 0.3),
                  child: ListTile(
                    leading: Text(item.emoji, style: const TextStyle(fontSize: 24)),
                    title: Text(item.title, style: const TextStyle(fontWeight: FontWeight.w600)),
                    subtitle: item.body != null ? Text(item.body!) : null,
                    trailing: item.isRead ? null : const Icon(Icons.circle, size: 10, color: Colors.red),
                    onTap: () async {
                      if (!item.isRead) {
                        await ref.read(notificationRepositoryProvider).markRead(item.id);
                        ref.invalidate(notificationListProvider);
                        ref.invalidate(unreadNotificationCountProvider);
                      }
                    },
                  ),
                );
              },
            );
          },
        ),
      ),
    );
  }
}

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/utils/formatters.dart';
import '../../../core/widgets/fc/fc.dart';
import '../data/app_notification.dart';
import '../data/notification_repository.dart';
import '../providers/notification_providers.dart';

/// A visitor notification opens what it is about: the request for a resident
/// to answer, the gate register for a guard's approved/rejected result.
void _openTarget(BuildContext context, AppNotification item) {
  final visitorId = item.visitorId;
  if (visitorId == null) return;

  if (item.type == 'visitor_request') {
    context.push('/visitors/request/$visitorId');
  } else if (item.type.startsWith('visitor_request_')) {
    context.push('/gatekeeper/visitors');
  }
}

/// Icon + accent per notification type. Visitor requests use the warning
/// accent so they stand out as the one thing that needs an answer.
(IconData, Color) _styleFor(String type) => switch (type) {
      'visitor_request' => (Icons.doorbell_rounded, AppColors.warning),
      'visitor_request_approved' => (Icons.verified_rounded, AppColors.success),
      'visitor_request_rejected' => (Icons.block_rounded, AppColors.danger),
      'visitor_arrived' => (Icons.meeting_room_rounded, AppColors.accentTeal),
      'maintenance_due' => (Icons.receipt_long_rounded, AppColors.accentAmber),
      'request_status' => (Icons.build_circle_outlined, AppColors.accentIndigo),
      'new_notice' => (Icons.campaign_rounded, AppColors.accentSky),
      'event_reminder' => (Icons.event_rounded, AppColors.accentViolet),
      _ => (Icons.notifications_rounded, AppColors.primary),
    };

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
          skeleton: true,
          onRetry: () => ref.invalidate(notificationListProvider),
          builder: (context, items) {
            if (items.isEmpty) {
              return const EmptyState(
                title: "You're all caught up",
                message: 'Visitor requests, notices and bill reminders will show up here.',
                icon: Icons.notifications_none_rounded,
              );
            }

            return ListView.separated(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              itemCount: items.length,
              separatorBuilder: (_, __) => const SizedBox(height: 10),
              itemBuilder: (context, index) {
                final item = items[index];

                final (icon, color) = _styleFor(item.type);
                final isRequest = item.type == 'visitor_request';

                return FcListCard(
                  highlight: !item.isRead,
                  leading: FcIconBox(icon: icon, color: color),
                  title: item.title,
                  titleStyle: TextStyle(
                    fontSize: 15.5,
                    fontWeight: item.isRead ? FontWeight.w600 : FontWeight.w800,
                    color: AppColors.textPrimary,
                  ),
                  subtitle: item.body,
                  meta: [if (item.createdAt != null) FcMeta(Icons.schedule_rounded, formatRelative(item.createdAt))],
                  badges: [
                    if (isRequest && !item.isRead)
                      const FcBadge(label: 'Action needed', color: AppColors.warning, icon: Icons.priority_high_rounded),
                  ],
                  trailing: item.isRead
                      ? null
                      : Padding(
                          padding: const EdgeInsets.only(top: 6, right: 8, left: 4),
                          child: Container(
                            width: 10,
                            height: 10,
                            decoration: const BoxDecoration(color: AppColors.primary, shape: BoxShape.circle),
                          ),
                        ),
                  onTap: () async {
                    if (!item.isRead) {
                      await ref.read(notificationRepositoryProvider).markRead(item.id);
                      ref.invalidate(notificationListProvider);
                      ref.invalidate(unreadNotificationCountProvider);
                    }
                    if (context.mounted) _openTarget(context, item);
                  },
                );
              },
            );
          },
        ),
      ),
    );
  }
}

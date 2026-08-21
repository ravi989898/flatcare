import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/widgets/async_view.dart';
import '../data/announcement.dart';
import '../providers/announcement_providers.dart';

class AnnouncementListScreen extends ConsumerWidget {
  const AnnouncementListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final announcements = ref.watch(announcementListProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Announcements')),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(announcementListProvider.future),
        child: AsyncView<List<Announcement>>(
          value: announcements,
          onRetry: () => ref.invalidate(announcementListProvider),
          builder: (context, items) {
            if (items.isEmpty) {
              return const EmptyState(message: 'No announcements yet.', icon: Icons.campaign_outlined);
            }

            return ListView.separated(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              itemCount: items.length,
              separatorBuilder: (_, __) => const SizedBox(height: 8),
              itemBuilder: (context, index) {
                final item = items[index];

                return Card(
                  child: ListTile(
                    leading: item.isPinned ? const Icon(Icons.push_pin, size: 18) : null,
                    title: Text(item.title, style: const TextStyle(fontWeight: FontWeight.w600)),
                    subtitle: Padding(
                      padding: const EdgeInsets.only(top: 4),
                      child: Text(
                        item.body,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    onTap: () => context.push('/announcements/${item.id}'),
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

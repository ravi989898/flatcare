import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/photo_avatar.dart';
import '../data/committee_member.dart';
import '../providers/committee_providers.dart';

class CommitteeMembersScreen extends ConsumerWidget {
  const CommitteeMembersScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final members = ref.watch(committeeMemberListProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Committee Members')),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(committeeMemberListProvider.future),
        child: AsyncView<List<CommitteeMember>>(
          value: members,
          onRetry: () => ref.invalidate(committeeMemberListProvider),
          builder: (context, items) {
            if (items.isEmpty) {
              return const EmptyState(message: 'No committee members listed yet.', icon: Icons.groups_outlined);
            }

            return ListView.separated(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              itemCount: items.length,
              separatorBuilder: (_, __) => const SizedBox(height: 8),
              itemBuilder: (context, index) {
                final member = items[index];

                return Card(
                  child: ListTile(
                    leading: PhotoAvatar(url: member.photoUrl, name: member.name, radius: 22),
                    title: Text(member.name, style: const TextStyle(fontWeight: FontWeight.w600)),
                    subtitle: Text(member.position ?? 'Committee Member'),
                    trailing: member.phone != null
                        ? IconButton(
                            icon: const Icon(Icons.call, color: Colors.green),
                            onPressed: () => launchUrl(Uri.parse('tel:${member.phone}')),
                          )
                        : null,
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

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/status_chip.dart';
import '../data/complaint.dart';
import '../providers/complaint_providers.dart';

class ComplaintListScreen extends ConsumerWidget {
  const ComplaintListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final complaints = ref.watch(complaintListProvider(null));

    return Scaffold(
      appBar: AppBar(title: const Text('Complaints')),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => context.push('/complaints/new'),
        icon: const Icon(Icons.add),
        label: const Text('New Complaint'),
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(complaintListProvider(null).future),
        child: AsyncView<List<Complaint>>(
          value: complaints,
          onRetry: () => ref.invalidate(complaintListProvider(null)),
          builder: (context, items) {
            if (items.isEmpty) {
              return const EmptyState(
                message: 'No complaints yet.\nTap "New Complaint" to raise one.',
                icon: Icons.report_gmailerrorred_outlined,
              );
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
                    title: Text(item.subject, style: const TextStyle(fontWeight: FontWeight.w600)),
                    subtitle: Padding(
                      padding: const EdgeInsets.only(top: 4),
                      child: Text('${item.category.replaceAll('_', ' ')} · ${item.flat?.displayLabel ?? ''}'),
                    ),
                    trailing: StatusChip(label: item.status),
                    onTap: () => context.push('/complaints/${item.id}'),
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

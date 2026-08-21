import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/status_chip.dart';
import '../data/visitor.dart';
import '../providers/visitor_providers.dart';

class VisitorListScreen extends ConsumerWidget {
  const VisitorListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final visitors = ref.watch(visitorListProvider(null));

    return Scaffold(
      appBar: AppBar(title: const Text('Visitors')),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(visitorListProvider(null).future),
        child: AsyncView<List<Visitor>>(
          value: visitors,
          onRetry: () => ref.invalidate(visitorListProvider(null)),
          builder: (context, items) {
            if (items.isEmpty) {
              return const EmptyState(message: 'No visitor entries yet for your flat.', icon: Icons.people_outline);
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
                    leading: const Icon(Icons.person_outline),
                    title: Text(item.visitorName, style: const TextStyle(fontWeight: FontWeight.w600)),
                    subtitle: Padding(
                      padding: const EdgeInsets.only(top: 4),
                      child: Text('${item.purpose} · ${item.checkInAt ?? ''}'),
                    ),
                    trailing: StatusChip(label: item.status),
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

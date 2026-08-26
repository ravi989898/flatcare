import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/status_chip.dart';
import '../data/maintenance_request.dart';
import '../providers/maintenance_request_providers.dart';

class MaintenanceRequestListScreen extends ConsumerWidget {
  const MaintenanceRequestListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final requests = ref.watch(maintenanceRequestListProvider(null));

    return Scaffold(
      appBar: AppBar(title: const Text('Maintenance Requests')),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => context.push('/maintenance-requests/new'),
        icon: const Icon(Icons.add),
        label: const Text('New Request'),
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(maintenanceRequestListProvider(null).future),
        child: AsyncView<List<MaintenanceRequest>>(
          value: requests,
          onRetry: () => ref.invalidate(maintenanceRequestListProvider(null)),
          builder: (context, items) {
            if (items.isEmpty) {
              return const EmptyState(
                message: 'No maintenance requests yet.\nTap "New Request" to log a repair issue.',
                icon: Icons.build_outlined,
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
                    title: Text(item.title, style: const TextStyle(fontWeight: FontWeight.w600)),
                    subtitle: Padding(
                      padding: const EdgeInsets.only(top: 4),
                      child: Text('${item.category.replaceAll('_', ' ')} · ${item.flat?.displayLabel ?? ''}'),
                    ),
                    trailing: StatusChip(label: item.status),
                    onTap: () => context.push('/maintenance-requests/${item.id}'),
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

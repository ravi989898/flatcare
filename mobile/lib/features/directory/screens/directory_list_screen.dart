import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/async_view.dart';
import '../data/directory_entry.dart';
import '../providers/directory_providers.dart';

class DirectoryListScreen extends ConsumerWidget {
  const DirectoryListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final entries = ref.watch(directoryListProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Directory')),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: TextField(
              decoration: const InputDecoration(
                labelText: 'Search by name, phone or flat',
                prefixIcon: Icon(Icons.search),
              ),
              onChanged: (value) => ref.read(directorySearchProvider.notifier).state = value,
            ),
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => ref.refresh(directoryListProvider.future),
              child: AsyncView<List<DirectoryEntry>>(
                value: entries,
                onRetry: () => ref.invalidate(directoryListProvider),
                builder: (context, items) {
                  if (items.isEmpty) {
                    return const EmptyState(message: 'No residents found.', icon: Icons.people_alt_outlined);
                  }

                  return ListView.separated(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    itemCount: items.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 8),
                    itemBuilder: (context, index) {
                      final item = items[index];

                      return Card(
                        child: ListTile(
                          leading: const CircleAvatar(child: Icon(Icons.person_outline)),
                          title: Text(item.name ?? '—', style: const TextStyle(fontWeight: FontWeight.w600)),
                          subtitle: Padding(
                            padding: const EdgeInsets.only(top: 4),
                            child: Text(
                              '${item.flat.displayLabel} · ${item.residentType}'
                              '${item.phone != null ? ' · ${item.phone}' : ''}',
                            ),
                          ),
                        ),
                      );
                    },
                  );
                },
              ),
            ),
          ),
        ],
      ),
    );
  }
}

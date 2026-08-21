import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/status_chip.dart';
import '../data/bill.dart';
import '../providers/bill_providers.dart';

class BillListScreen extends ConsumerWidget {
  const BillListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final bills = ref.watch(billListProvider(null));

    return Scaffold(
      appBar: AppBar(title: const Text('Maintenance Bills')),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(billListProvider(null).future),
        child: AsyncView<List<Bill>>(
          value: bills,
          onRetry: () => ref.invalidate(billListProvider(null)),
          builder: (context, items) {
            if (items.isEmpty) {
              return const EmptyState(message: 'No bills raised yet.', icon: Icons.receipt_long_outlined);
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
                      child: Text('₹${item.amount.toStringAsFixed(0)} · Due ${item.dueDate ?? '-'}'),
                    ),
                    trailing: StatusChip(label: item.status),
                    onTap: () => context.push('/bills/${item.id}'),
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

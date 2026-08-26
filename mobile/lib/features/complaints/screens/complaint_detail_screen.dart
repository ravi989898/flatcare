import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/status_chip.dart';
import '../data/complaint.dart';
import '../providers/complaint_providers.dart';

class ComplaintDetailScreen extends ConsumerWidget {
  const ComplaintDetailScreen({super.key, required this.id});

  final int id;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final complaint = ref.watch(complaintDetailProvider(id));

    return Scaffold(
      appBar: AppBar(title: const Text('Complaint Details')),
      body: AsyncView<Complaint>(
        value: complaint,
        onRetry: () => ref.invalidate(complaintDetailProvider(id)),
        builder: (context, item) {
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(child: Text(item.subject, style: Theme.of(context).textTheme.titleLarge)),
                  StatusChip(label: item.status),
                ],
              ),
              const SizedBox(height: 4),
              Text(
                '${item.category.replaceAll('_', ' ')} · ${item.priority} priority',
                style: Theme.of(context).textTheme.bodySmall,
              ),
              const SizedBox(height: 16),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('Description', style: Theme.of(context).textTheme.labelLarge),
                      const SizedBox(height: 4),
                      Text(item.description),
                      if (item.against != null && item.against!.isNotEmpty) ...[
                        const SizedBox(height: 12),
                        Text('Against', style: Theme.of(context).textTheme.labelLarge),
                        const SizedBox(height: 4),
                        Text(item.against!),
                      ],
                      if (item.flat != null) ...[
                        const SizedBox(height: 12),
                        Text('Flat', style: Theme.of(context).textTheme.labelLarge),
                        const SizedBox(height: 4),
                        Text(item.flat!.displayLabel),
                      ],
                      if (item.resolutionNotes != null && item.resolutionNotes!.isNotEmpty) ...[
                        const SizedBox(height: 12),
                        Text('Resolution notes', style: Theme.of(context).textTheme.labelLarge),
                        const SizedBox(height: 4),
                        Text(item.resolutionNotes!),
                      ],
                    ],
                  ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}

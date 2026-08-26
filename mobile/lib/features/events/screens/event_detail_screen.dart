import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/async_view.dart';
import '../data/event.dart';
import '../providers/event_providers.dart';

class EventDetailScreen extends ConsumerWidget {
  const EventDetailScreen({super.key, required this.id});

  final int id;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final event = ref.watch(eventDetailProvider(id));

    return Scaffold(
      appBar: AppBar(title: const Text('Event')),
      body: AsyncView<Event>(
        value: event,
        onRetry: () => ref.invalidate(eventDetailProvider(id)),
        builder: (context, item) {
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              Text(item.title, style: Theme.of(context).textTheme.titleLarge),
              const SizedBox(height: 4),
              Text(
                [item.category, item.location].whereType<String>().join(' · '),
                style: Theme.of(context).textTheme.bodySmall,
              ),
              const SizedBox(height: 8),
              if (item.startAt != null)
                Row(
                  children: [
                    const Icon(Icons.schedule, size: 18),
                    const SizedBox(width: 8),
                    Text(item.startAt!),
                  ],
                ),
              const SizedBox(height: 16),
              if (item.description != null) Text(item.description!),
            ],
          );
        },
      ),
    );
  }
}

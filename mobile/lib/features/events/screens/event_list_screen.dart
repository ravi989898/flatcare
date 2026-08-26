import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/widgets/async_view.dart';
import '../data/event.dart';
import '../providers/event_providers.dart';

class EventListScreen extends ConsumerStatefulWidget {
  const EventListScreen({super.key});

  @override
  ConsumerState<EventListScreen> createState() => _EventListScreenState();
}

class _EventListScreenState extends ConsumerState<EventListScreen> with SingleTickerProviderStateMixin {
  late final TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Events'),
        bottom: TabBar(
          controller: _tabController,
          tabs: const [Tab(text: 'Upcoming'), Tab(text: 'Past')],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: const [
          _EventList(when: 'upcoming'),
          _EventList(when: 'past'),
        ],
      ),
    );
  }
}

class _EventList extends ConsumerWidget {
  const _EventList({required this.when});

  final String when;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final events = ref.watch(eventListProvider(when));

    return RefreshIndicator(
      onRefresh: () => ref.refresh(eventListProvider(when).future),
      child: AsyncView<List<Event>>(
        value: events,
        onRetry: () => ref.invalidate(eventListProvider(when)),
        builder: (context, items) {
          if (items.isEmpty) {
            return EmptyState(message: 'No $when events.', icon: Icons.event_outlined);
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
                  leading: const Icon(Icons.event_outlined),
                  title: Text(item.title, style: const TextStyle(fontWeight: FontWeight.w600)),
                  subtitle: Padding(
                    padding: const EdgeInsets.only(top: 4),
                    child: Text([item.startAt, item.location].whereType<String>().join(' · ')),
                  ),
                  onTap: () => context.push('/events/${item.id}'),
                ),
              );
            },
          );
        },
      ),
    );
  }
}

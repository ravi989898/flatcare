import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_exception.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/status_chip.dart';
import '../data/poll.dart';
import '../data/poll_repository.dart';
import '../providers/poll_providers.dart';

class PollListScreen extends ConsumerWidget {
  const PollListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final polls = ref.watch(pollListProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Polls & Surveys')),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(pollListProvider.future),
        child: AsyncView<List<Poll>>(
          value: polls,
          onRetry: () => ref.invalidate(pollListProvider),
          builder: (context, items) {
            if (items.isEmpty) {
              return const EmptyState(message: 'No polls right now.', icon: Icons.bar_chart_outlined);
            }

            return ListView.separated(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              itemCount: items.length,
              separatorBuilder: (_, __) => const SizedBox(height: 12),
              itemBuilder: (context, index) => _PollCard(poll: items[index]),
            );
          },
        ),
      ),
    );
  }
}

class _PollCard extends ConsumerStatefulWidget {
  const _PollCard({required this.poll});

  final Poll poll;

  @override
  ConsumerState<_PollCard> createState() => _PollCardState();
}

class _PollCardState extends ConsumerState<_PollCard> {
  bool _voting = false;

  Future<void> _vote(int optionId) async {
    setState(() => _voting = true);
    try {
      await ref.read(pollRepositoryProvider).vote(widget.poll.id, optionId);
      ref.invalidate(pollListProvider);
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _voting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final poll = widget.poll;
    final showResults = poll.hasVoted || !poll.isOpen;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(child: Text(poll.question, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15))),
                StatusChip(label: poll.status),
              ],
            ),
            if (poll.description != null) ...[
              const SizedBox(height: 4),
              Text(poll.description!, style: const TextStyle(color: Colors.black54, fontSize: 13)),
            ],
            const SizedBox(height: 12),
            for (final option in poll.options) ...[
              if (showResults)
                _ResultBar(option: option, isMine: option.id == poll.myOptionId)
              else
                Padding(
                  padding: const EdgeInsets.only(bottom: 8),
                  child: OutlinedButton(
                    onPressed: _voting ? null : () => _vote(option.id),
                    style: OutlinedButton.styleFrom(alignment: Alignment.centerLeft),
                    child: Text(option.label),
                  ),
                ),
            ],
            const SizedBox(height: 4),
            Text('${poll.totalVotes} vote${poll.totalVotes == 1 ? '' : 's'}', style: const TextStyle(color: Colors.black45, fontSize: 12)),
          ],
        ),
      ),
    );
  }
}

class _ResultBar extends StatelessWidget {
  const _ResultBar({required this.option, required this.isMine});

  final PollOption option;
  final bool isMine;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  option.label,
                  style: TextStyle(fontWeight: isMine ? FontWeight.w700 : FontWeight.w500),
                ),
              ),
              if (isMine) const Icon(Icons.check_circle, size: 16, color: AppTheme.brandBlue),
              const SizedBox(width: 4),
              Text('${option.percentage.round()}%', style: const TextStyle(fontSize: 12, color: Colors.black54)),
            ],
          ),
          const SizedBox(height: 4),
          ClipRRect(
            borderRadius: BorderRadius.circular(999),
            child: LinearProgressIndicator(
              value: option.percentage / 100,
              minHeight: 6,
              backgroundColor: const Color(0xFFEFF2F7),
              color: AppTheme.brandBlue,
            ),
          ),
        ],
      ),
    );
  }
}

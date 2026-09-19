import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/api/api_exception.dart';
import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/photo_avatar.dart';
import '../data/daily_helper.dart';
import '../data/daily_helper_repository.dart';
import '../providers/visitor_providers.dart';
import '../widgets/visitor_ui.dart';

/// "Daily Helper" — the household's regular helpers (maid, cook, driver...).
class DailyHelperScreen extends ConsumerWidget {
  const DailyHelperScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final helpers = ref.watch(dailyHelperListProvider);

    return VisitorScaffold(
      title: 'Daily Helper',
      fab: VisitorFab(tooltip: 'Add helper', onPressed: () => context.push('/visitors/helpers/new')),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(dailyHelperListProvider.future),
        child: AsyncView<List<DailyHelper>>(
          value: helpers,
          onRetry: () => ref.invalidate(dailyHelperListProvider),
          builder: (context, items) {
            if (items.isEmpty) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                children: const [
                  SizedBox(
                    height: 480,
                    child: VisitorEmptyState(
                      icon: Icons.cleaning_services_rounded,
                      badgeIcon: Icons.add_rounded,
                      color: Color(0xFFF5A623),
                      title: 'No Daily Helpers Added.',
                      message: 'Add daily helpers to manage their entry easily.',
                    ),
                  ),
                ],
              );
            }

            return ListView.separated(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 96),
              itemCount: items.length,
              separatorBuilder: (_, _) => const SizedBox(height: 12),
              itemBuilder: (context, index) => _HelperCard(helper: items[index]),
            );
          },
        ),
      ),
    );
  }
}

class _HelperCard extends ConsumerWidget {
  const _HelperCard({required this.helper});

  final DailyHelper helper;

  Future<void> _remove(BuildContext context, WidgetRef ref) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Remove helper?'),
        content: Text('${helper.name} will be removed from your daily helpers.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Keep')),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            style: TextButton.styleFrom(foregroundColor: VisitorColors.error),
            child: const Text('Remove'),
          ),
        ],
      ),
    );
    if (confirmed != true) return;

    try {
      await ref.read(dailyHelperRepositoryProvider).remove(helper.id);
      ref.invalidate(dailyHelperListProvider);
      if (context.mounted) showVisitorSnack(context, 'Helper removed');
    } on ApiException catch (e) {
      if (context.mounted) showVisitorSnack(context, e.message, error: true);
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return VisitorCardShell(
      child: Row(
        children: [
          PhotoAvatar(url: helper.photoUrl, name: helper.name, radius: 26),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(helper.name, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15.5, color: VisitorColors.text)),
                const SizedBox(height: 2),
                Text('${purposeLabel(helper.helperType)}  •  ${helper.phone}', style: const TextStyle(color: VisitorColors.muted, fontSize: 12.5)),
              ],
            ),
          ),
          IconButton(
            tooltip: 'Call ${helper.name}',
            onPressed: () => launchUrl(Uri.parse('tel:${helper.phone}')),
            icon: const Icon(Icons.call_rounded, color: VisitorColors.primary),
          ),
          IconButton(
            tooltip: 'Remove',
            onPressed: () => _remove(context, ref),
            icon: const Icon(Icons.delete_outline_rounded, color: VisitorColors.error),
          ),
        ],
      ),
    );
  }
}

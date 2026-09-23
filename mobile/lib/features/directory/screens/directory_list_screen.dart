import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/widgets/fc/fc.dart';
import '../data/directory_entry.dart';
import '../providers/directory_providers.dart';

Color _typeColor(String type) => switch (type.toLowerCase()) {
      'owner' => AppColors.accentTeal,
      'tenant' => AppColors.accentAmber,
      'occupant' => AppColors.accentViolet,
      _ => AppColors.accentSlate,
    };

IconData _typeIcon(String type) => switch (type.toLowerCase()) {
      'owner' => Icons.key_rounded,
      'tenant' => Icons.assignment_ind_outlined,
      _ => Icons.person_outline_rounded,
    };

String _titleCase(String value) => value.isEmpty ? value : value[0].toUpperCase() + value.substring(1);

/// A name to show even when the account has none on file.
String _displayName(DirectoryEntry entry) {
  final name = entry.name?.trim();
  return (name == null || name.isEmpty) ? 'Resident of ${entry.flat.flatNumber}' : name;
}

/// Masked numbers (••••••3633) can't be dialled — only a full number gets
/// the call action. Gatekeepers receive full numbers from the API.
bool _isCallable(String? phone) => phone != null && phone.isNotEmpty && !phone.contains('*');

class DirectoryListScreen extends ConsumerStatefulWidget {
  const DirectoryListScreen({super.key});

  @override
  ConsumerState<DirectoryListScreen> createState() => _DirectoryListScreenState();
}

class _DirectoryListScreenState extends ConsumerState<DirectoryListScreen> {
  final _scrollController = ScrollController();
  Timer? _debounce;

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(() {
      if (_scrollController.position.extentAfter < 400) {
        ref.read(directoryListProvider.notifier).loadMore();
      }
    });
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _scrollController.dispose();
    super.dispose();
  }

  void _onSearch(String value) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 350), () {
      ref.read(directorySearchProvider.notifier).state = value.trim();
    });
  }

  @override
  Widget build(BuildContext context) {
    final directory = ref.watch(directoryListProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Directory')),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 4, 16, 12),
            child: TextField(
              textInputAction: TextInputAction.search,
              decoration: const InputDecoration(
                hintText: 'Search name, phone, block or flat',
                prefixIcon: Icon(Icons.search_rounded),
              ),
              onChanged: _onSearch,
            ),
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => ref.refresh(directoryListProvider.future),
              child: AsyncView<DirectoryState>(
                value: directory,
                skeleton: true,
                onRetry: () => ref.invalidate(directoryListProvider),
                builder: (context, state) {
                  if (state.items.isEmpty) {
                    final searching = ref.read(directorySearchProvider).isNotEmpty;
                    return EmptyState(
                      icon: searching ? Icons.search_off_rounded : Icons.groups_2_outlined,
                      title: searching ? 'No matches' : 'No residents yet',
                      message: searching
                          ? 'No resident matches your search. Try a name, phone number, block or flat number.'
                          : 'Residents will appear here once your society admin adds them.',
                    );
                  }

                  return ListView.separated(
                    controller: _scrollController,
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
                    itemCount: state.items.length + (state.hasMore ? 1 : 0),
                    separatorBuilder: (_, __) => const SizedBox(height: 12),
                    itemBuilder: (context, index) {
                      if (index == state.items.length) {
                        return const Padding(
                          padding: EdgeInsets.symmetric(vertical: 16),
                          child: Center(child: CircularProgressIndicator()),
                        );
                      }
                      return _ResidentCard(entry: state.items[index]);
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

class _ResidentCard extends StatelessWidget {
  const _ResidentCard({required this.entry});

  final DirectoryEntry entry;

  @override
  Widget build(BuildContext context) {
    final color = _typeColor(entry.residentType);
    final name = _displayName(entry);
    final flat = entry.flat;
    final location = [
      if (flat.blockName != null && flat.blockName!.isNotEmpty) flat.blockName!,
      'Flat ${flat.flatNumber}',
    ].join(' · ');

    return FcListCard(
      leading: FcInitialsAvatar(name: name, color: color),
      title: name,
      meta: [
        FcMeta(Icons.apartment_rounded, location),
        if (entry.phone != null && entry.phone!.isNotEmpty) FcMeta(Icons.phone_outlined, entry.phone!),
      ],
      badges: [
        FcBadge(label: _titleCase(entry.residentType), color: color, icon: _typeIcon(entry.residentType)),
        if (entry.isPrimary) const FcBadge(label: 'Primary', color: AppColors.primary, icon: Icons.star_rounded),
      ],
      trailing: _isCallable(entry.phone)
          ? Padding(
              padding: const EdgeInsets.only(right: 6),
              child: FcCardAction(
                icon: Icons.call_rounded,
                color: AppColors.success,
                tooltip: 'Call $name',
                onPressed: () => launchUrl(Uri.parse('tel:${entry.phone}')),
              ),
            )
          : null,
    );
  }
}

import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/widgets/fc/fc.dart';
import '../data/directory_entry.dart';
import '../providers/directory_providers.dart';

/// Owner / tenant / occupant — color, icon and label used on chips, cards
/// and the detail sheet.
class _TypeStyle {
  const _TypeStyle(this.label, this.color, this.icon);

  final String label;
  final Color color;
  final IconData icon;
}

const _types = {
  'owner': _TypeStyle('Owner', AppColors.accentTeal, Icons.key_rounded),
  'tenant': _TypeStyle('Tenant', AppColors.accentAmber, Icons.assignment_ind_rounded),
  'occupant': _TypeStyle('Occupant', AppColors.accentViolet, Icons.person_rounded),
};

_TypeStyle _typeOf(String type) {
  final known = _types[type.toLowerCase()];
  if (known != null) return known;
  final label = type.isEmpty ? 'Resident' : type[0].toUpperCase() + type.substring(1);
  return _TypeStyle(label, AppColors.accentSlate, Icons.person_outline_rounded);
}

/// A name to show even when the account has none on file.
String _displayName(DirectoryEntry entry) {
  final name = entry.name?.trim();
  return (name == null || name.isEmpty) ? 'Resident of ${entry.flat.flatNumber}' : name;
}

/// Masked numbers (••••••3633) can't be dialled — only a full number gets
/// the call/WhatsApp actions. Gatekeepers receive full numbers from the API.
bool _isCallable(String? phone) => phone != null && phone.isNotEmpty && !phone.contains('*');

Future<void> _call(String phone) => launchUrl(Uri.parse('tel:$phone'));

Future<void> _whatsApp(String phone) {
  var digits = phone.replaceAll(RegExp(r'\D'), '');
  if (digits.length == 10) digits = '91$digits';
  return launchUrl(Uri.parse('https://wa.me/$digits'), mode: LaunchMode.externalApplication);
}

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
    final selectedType = ref.watch(directoryTypeProvider);
    final total = directory.valueOrNull?.total;

    return Scaffold(
      appBar: AppBar(title: const Text('Directory')),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
            child: _SearchHeader(total: total, onChanged: _onSearch),
          ),
          SizedBox(
            height: 40,
            child: ListView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 16),
              children: [
                _TypeChip(
                  label: 'All',
                  icon: Icons.groups_2_rounded,
                  color: AppColors.primary,
                  selected: selectedType == null,
                  onTap: () => ref.read(directoryTypeProvider.notifier).state = null,
                ),
                for (final entry in _types.entries) ...[
                  const SizedBox(width: 8),
                  _TypeChip(
                    label: '${entry.value.label}s',
                    icon: entry.value.icon,
                    color: entry.value.color,
                    selected: selectedType == entry.key,
                    onTap: () => ref.read(directoryTypeProvider.notifier).state = entry.key,
                  ),
                ],
              ],
            ),
          ),
          const SizedBox(height: 10),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => ref.refresh(directoryListProvider.future),
              child: AsyncView<DirectoryState>(
                value: directory,
                skeleton: true,
                onRetry: () => ref.invalidate(directoryListProvider),
                builder: (context, state) {
                  if (state.items.isEmpty) {
                    final filtering = ref.read(directorySearchProvider).isNotEmpty || selectedType != null;
                    return EmptyState(
                      icon: filtering ? Icons.search_off_rounded : Icons.groups_2_outlined,
                      title: filtering ? 'No matches' : 'No residents yet',
                      message: filtering
                          ? 'No resident matches your search. Try a name, phone number, block or flat number.'
                          : 'Residents will appear here once your society admin adds them.',
                    );
                  }

                  return ListView.separated(
                    controller: _scrollController,
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.fromLTRB(16, 2, 16, 24),
                    itemCount: state.items.length + (state.hasMore ? 1 : 0),
                    separatorBuilder: (_, _) => const SizedBox(height: 12),
                    itemBuilder: (context, index) {
                      if (index == state.items.length) {
                        return const Padding(
                          padding: EdgeInsets.symmetric(vertical: 16),
                          child: Center(child: CircularProgressIndicator()),
                        );
                      }
                      final entry = state.items[index];
                      return _ResidentCard(entry: entry, onTap: () => _showDetails(context, entry));
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

  void _showDetails(BuildContext context, DirectoryEntry entry) {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      showDragHandle: false,
      builder: (_) => _ResidentSheet(entry: entry),
    );
  }
}

class _SearchHeader extends StatelessWidget {
  const _SearchHeader({required this.total, required this.onChanged});

  final int? total;
  final ValueChanged<String> onChanged;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 16),
      decoration: BoxDecoration(gradient: AppColors.primaryGradient, borderRadius: BorderRadius.circular(20)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.22), borderRadius: BorderRadius.circular(14)),
                child: const Icon(Icons.diversity_3_rounded, color: Colors.white),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      total == null ? 'Residents' : '$total ${total == 1 ? 'resident' : 'residents'}',
                      style: const TextStyle(color: Colors.white, fontSize: 19, fontWeight: FontWeight.w800),
                    ),
                    const Text(
                      'Find anyone in your society',
                      style: TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.w500),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          TextField(
            textInputAction: TextInputAction.search,
            onChanged: onChanged,
            decoration: InputDecoration(
              hintText: 'Search name, phone, block or flat',
              prefixIcon: const Icon(Icons.search_rounded),
              fillColor: Colors.white,
              enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: BorderSide.none),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(14),
                borderSide: const BorderSide(color: Colors.white, width: 2),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _TypeChip extends StatelessWidget {
  const _TypeChip({required this.label, required this.icon, required this.color, required this.selected, required this.onTap});

  final String label;
  final IconData icon;
  final Color color;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return ChoiceChip(
      selected: selected,
      showCheckmark: false,
      avatar: Icon(icon, size: 17, color: selected ? Colors.white : color),
      label: Text(label),
      labelStyle: TextStyle(fontWeight: FontWeight.w700, color: selected ? Colors.white : color),
      selectedColor: color,
      backgroundColor: AppColors.soft(color),
      side: BorderSide.none,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(999)),
      onSelected: (_) => onTap(),
    );
  }
}

class _ResidentCard extends StatelessWidget {
  const _ResidentCard({required this.entry, required this.onTap});

  final DirectoryEntry entry;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final type = _typeOf(entry.residentType);
    final name = _displayName(entry);
    final flat = entry.flat;
    final callable = _isCallable(entry.phone);

    return Container(
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(18),
        boxShadow: kCardShadow,
      ),
      clipBehavior: Clip.antiAlias,
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onTap,
          child: IntrinsicHeight(
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Container(width: 5, color: type.color),
                Expanded(
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(14, 14, 12, 12),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            FcInitialsAvatar(name: name, color: type.color, size: 50),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    name,
                                    maxLines: 2,
                                    overflow: TextOverflow.ellipsis,
                                    style: const TextStyle(fontSize: 16.5, fontWeight: FontWeight.w800, color: AppColors.textPrimary),
                                  ),
                                  const SizedBox(height: 4),
                                  if (flat.blockName != null && flat.blockName!.isNotEmpty)
                                    Text(flat.blockName!, style: const TextStyle(fontSize: 13.5, color: AppColors.textSecondary)),
                                  if (entry.phone != null && entry.phone!.isNotEmpty)
                                    Padding(
                                      padding: const EdgeInsets.only(top: 2),
                                      child: Row(
                                        children: [
                                          const Icon(Icons.phone_outlined, size: 15, color: AppColors.textSecondary),
                                          const SizedBox(width: 5),
                                          Text(entry.phone!, style: const TextStyle(fontSize: 13.5, color: AppColors.textSecondary)),
                                        ],
                                      ),
                                    ),
                                ],
                              ),
                            ),
                            const SizedBox(width: 8),
                            _FlatPill(flatNumber: flat.flatNumber, color: type.color),
                          ],
                        ),
                        const SizedBox(height: 10),
                        Row(
                          children: [
                            FcBadge(label: type.label, color: type.color, icon: type.icon),
                            if (entry.isPrimary) ...[
                              const SizedBox(width: 6),
                              const FcBadge(label: 'Primary', color: AppColors.primary, icon: Icons.star_rounded),
                            ],
                            const Spacer(),
                            if (callable) ...[
                              FcCardAction(
                                icon: Icons.chat_rounded,
                                color: AppColors.accentTeal,
                                tooltip: 'WhatsApp $name',
                                onPressed: () => _whatsApp(entry.phone!),
                              ),
                              const SizedBox(width: 8),
                              FcCardAction(
                                icon: Icons.call_rounded,
                                color: AppColors.success,
                                tooltip: 'Call $name',
                                onPressed: () => _call(entry.phone!),
                              ),
                            ],
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _FlatPill extends StatelessWidget {
  const _FlatPill({required this.flatNumber, required this.color});

  final String flatNumber;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: AppColors.soft(color),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: 0.3)),
      ),
      child: Column(
        children: [
          Text('FLAT', style: TextStyle(fontSize: 9.5, fontWeight: FontWeight.w800, color: color, letterSpacing: 0.8)),
          Text(flatNumber, style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: color)),
        ],
      ),
    );
  }
}

/// Tap a resident → everything about them, with big Call / WhatsApp /
/// Copy buttons.
class _ResidentSheet extends StatelessWidget {
  const _ResidentSheet({required this.entry});

  final DirectoryEntry entry;

  @override
  Widget build(BuildContext context) {
    final type = _typeOf(entry.residentType);
    final name = _displayName(entry);
    final flat = entry.flat;
    final callable = _isCallable(entry.phone);

    Widget row(IconData icon, String label, String value) => Padding(
          padding: const EdgeInsets.symmetric(vertical: 8),
          child: Row(
            children: [
              FcIconBox(icon: icon, color: type.color, size: 38),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(label, style: const TextStyle(fontSize: 12, color: AppColors.textSecondary, fontWeight: FontWeight.w600)),
                    Text(value, style: const TextStyle(fontSize: 15.5, color: AppColors.textPrimary, fontWeight: FontWeight.w700)),
                  ],
                ),
              ),
            ],
          ),
        );

    return SingleChildScrollView(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Container(
            padding: const EdgeInsets.fromLTRB(20, 28, 20, 22),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [type.color.withValues(alpha: 0.75), type.color],
              ),
              borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
            ),
            child: Column(
              children: [
                Container(
                  width: 78,
                  height: 78,
                  alignment: Alignment.center,
                  decoration: BoxDecoration(
                    color: Colors.white,
                    shape: BoxShape.circle,
                    border: Border.all(color: Colors.white.withValues(alpha: 0.6), width: 4),
                  ),
                  child: Text(
                    FcInitialsAvatar.initialsOf(name),
                    style: TextStyle(color: type.color, fontSize: 28, fontWeight: FontWeight.w800),
                  ),
                ),
                const SizedBox(height: 12),
                Text(
                  name,
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: Colors.white, fontSize: 21, fontWeight: FontWeight.w800),
                ),
                const SizedBox(height: 4),
                Text(
                  '${type.label}${entry.isPrimary ? ' · Primary member' : ''}',
                  style: const TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.w600),
                ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 12, 20, 8),
            child: Column(
              children: [
                if (flat.blockName != null && flat.blockName!.isNotEmpty) row(Icons.domain_rounded, 'Block', flat.blockName!),
                row(Icons.door_front_door_rounded, 'Flat', flat.flatNumber),
                if (entry.phone != null && entry.phone!.isNotEmpty) row(Icons.phone_rounded, 'Phone', entry.phone!),
              ],
            ),
          ),
          if (callable)
            SafeArea(
              top: false,
              child: Padding(
                padding: const EdgeInsets.fromLTRB(20, 4, 20, 20),
                child: Row(
                  children: [
                    Expanded(
                      child: FilledButton.icon(
                        style: FilledButton.styleFrom(backgroundColor: AppColors.success),
                        onPressed: () => _call(entry.phone!),
                        icon: const Icon(Icons.call_rounded),
                        label: const Text('Call'),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: FilledButton.icon(
                        style: FilledButton.styleFrom(backgroundColor: AppColors.accentTeal),
                        onPressed: () => _whatsApp(entry.phone!),
                        icon: const Icon(Icons.chat_rounded),
                        label: const Text('WhatsApp'),
                      ),
                    ),
                    const SizedBox(width: 10),
                    IconButton.filledTonal(
                      tooltip: 'Copy number',
                      style: IconButton.styleFrom(fixedSize: const Size(50, 50)),
                      onPressed: () {
                        Clipboard.setData(ClipboardData(text: entry.phone!));
                        showFcSnack(context, 'Number copied.');
                        Navigator.of(context).pop();
                      },
                      icon: const Icon(Icons.copy_rounded),
                    ),
                  ],
                ),
              ),
            )
          else
            const SizedBox(height: 20),
        ],
      ),
    );
  }
}

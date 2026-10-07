import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';

import '../../../core/api/api_exception.dart';
import '../../../core/models/flat.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/async_view.dart';
import '../../visitors/data/visitor.dart';
import '../data/guard_visitor_repository.dart';
import '../providers/gatekeeper_providers.dart';
import '../widgets/purpose_style.dart';

/// Walk-in check-in — a visitor who showed up without a resident's
/// pre-invite. Modeled on features/visitors/screens/invite_visitor_screen.dart,
/// but the guard picks any flat in the society (Api\V1\Guard\FlatController):
/// tap a block in the horizontal strip, then one of its flats - faster at a
/// gate than typing while someone's waiting (a search sheet is still one tap
/// away). Laid out to fit one screen, with Submit pinned at the bottom.
class GatekeeperVisitorCheckinScreen extends ConsumerStatefulWidget {
  const GatekeeperVisitorCheckinScreen({super.key});

  @override
  ConsumerState<GatekeeperVisitorCheckinScreen> createState() => _GatekeeperVisitorCheckinScreenState();
}

class _GatekeeperVisitorCheckinScreenState extends ConsumerState<GatekeeperVisitorCheckinScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _phoneController = TextEditingController();
  final _vehicleController = TextEditingController();
  String _purpose = Visitor.purposes.first;
  Flat? _flat;
  XFile? _photo;
  bool _isSubmitting = false;
  // A walk-in is sent to the resident for approval unless the guard opts out.
  bool _requiresApproval = true;

  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    _vehicleController.dispose();
    super.dispose();
  }

  /// Camera only, deliberately — no gallery option, so this can only ever
  /// be a photo of the person actually standing at the gate right now (see
  /// StoreGuardVisitorRequest's docblock on the backend).
  Future<void> _takePhoto() async {
    final photo = await ImagePicker().pickImage(source: ImageSource.camera, maxWidth: 1280, imageQuality: 85);
    if (photo != null) setState(() => _photo = photo);
  }

  Future<void> _pickFlat() async {
    final flat = await showModalBottomSheet<Flat>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => const _FlatPickerSheet(),
    );
    if (flat != null) setState(() => _flat = flat);
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate() || _flat == null) return;

    setState(() => _isSubmitting = true);
    try {
      await ref.read(guardVisitorRepositoryProvider).checkInWalkIn(
            flatId: _flat!.id,
            visitorName: _nameController.text.trim(),
            visitorPhone: _phoneController.text.trim(),
            purpose: _purpose,
            vehicleNumber: _vehicleController.text.trim(),
            photoPath: _photo?.path,
            requiresApproval: _requiresApproval,
          );
      ref.invalidate(guardVisitorListProvider);
      if (!mounted) return;
      final message = _requiresApproval
          ? 'Entry request sent to ${_flat!.displayLabel} for approval.'
          : '${_nameController.text.trim()} checked in.';
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
      context.pop();
    } on ApiException catch (e) {
      if (!mounted) return;
      // Laravel's default `message` for a multi-field validation failure is
      // just the first error plus "(and N more errors)" — list every
      // field's actual message instead so the guard knows exactly what to
      // fix (e.g. the photo being rejected vs. a missing flat).
      final fieldMessages = e.fieldErrors?.values.expand((messages) => messages).toList();
      final text = (fieldMessages != null && fieldMessages.isNotEmpty) ? fieldMessages.join('\n') : e.message;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(text)));
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final canSubmit = !_isSubmitting && _flat != null;

    return Scaffold(
      backgroundColor: const Color(0xFFF2F3F7),
      appBar: AppBar(
        title: const Text('Walk-in Check-in'),
        foregroundColor: Colors.white,
        flexibleSpace: const DecoratedBox(decoration: BoxDecoration(gradient: AppTheme.brandGradient)),
      ),
      body: SafeArea(
        child: Column(
          children: [
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.fromLTRB(12, 12, 12, 8),
                child: Form(
                  key: _formKey,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      _SectionCard(
                        title: 'Which flat?',
                        child: _BlockFlatPicker(
                          selected: _flat,
                          onSelected: (flat) => setState(() => _flat = flat),
                          onSearch: _pickFlat,
                        ),
                      ),
                      const SizedBox(height: 10),
                      _SectionCard(
                        title: 'Visitor details',
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            _PhotoField(photo: _photo, onTakePhoto: _takePhoto, onRemove: () => setState(() => _photo = null)),
                            const SizedBox(height: 10),
                            TextFormField(
                              controller: _nameController,
                              decoration: const InputDecoration(
                                labelText: 'Visitor Name',
                                prefixIcon: Icon(Icons.badge_outlined),
                                isDense: true,
                              ),
                              validator: (value) => (value == null || value.trim().isEmpty) ? "Enter the visitor's name" : null,
                            ),
                            const SizedBox(height: 10),
                            Row(
                              children: [
                                Expanded(
                                  child: TextFormField(
                                    controller: _phoneController,
                                    keyboardType: TextInputType.phone,
                                    decoration: const InputDecoration(
                                      labelText: 'Phone (optional)',
                                      prefixIcon: Icon(Icons.phone_outlined),
                                      isDense: true,
                                    ),
                                  ),
                                ),
                                const SizedBox(width: 8),
                                Expanded(
                                  child: TextFormField(
                                    controller: _vehicleController,
                                    decoration: const InputDecoration(
                                      labelText: 'Vehicle (optional)',
                                      prefixIcon: Icon(Icons.directions_car_outlined),
                                      isDense: true,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 10),
                      _SectionCard(
                        title: 'Purpose of visit',
                        child: Wrap(
                          spacing: 6,
                          runSpacing: 6,
                          children: [
                            for (final purpose in Visitor.purposes) _PurposeChip(
                              purpose: purpose,
                              selected: purpose == _purpose,
                              onTap: () => setState(() => _purpose = purpose),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 10),
                      Container(
                        padding: const EdgeInsets.only(left: 12, right: 4),
                        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(14)),
                        child: Row(
                          children: [
                            const Expanded(
                              child: Text('Ask resident to approve', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13.5)),
                            ),
                            Switch.adaptive(
                              value: _requiresApproval,
                              onChanged: (value) => setState(() => _requiresApproval = value),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
            // Pinned, so it's always on screen - no scrolling down to submit.
            Padding(
              padding: const EdgeInsets.fromLTRB(12, 4, 12, 12),
              child: DecoratedBox(
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(28),
                  gradient: canSubmit ? AppTheme.brandGradient : null,
                  color: canSubmit ? null : Colors.grey.shade300,
                ),
                child: SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.transparent,
                      shadowColor: Colors.transparent,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(28)),
                    ),
                    onPressed: canSubmit ? _submit : null,
                    child: _isSubmitting
                        ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                        : Text(_requiresApproval ? 'Send Request' : 'Check In', style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _SectionCard extends StatelessWidget {
  const _SectionCard({required this.title, required this.child});

  final String title;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 8, offset: const Offset(0, 2))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w700, color: AppTheme.brandBlueDark, letterSpacing: .2),
          ),
          const SizedBox(height: 8),
          child,
        ],
      ),
    );
  }
}

class _PurposeChip extends StatelessWidget {
  const _PurposeChip({required this.purpose, required this.selected, required this.onTap});

  final String purpose;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final style = PurposeStyle.of(purpose);

    return InkWell(
      borderRadius: BorderRadius.circular(999),
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 7),
        decoration: BoxDecoration(
          color: selected ? style.color : style.color.withValues(alpha: 0.1),
          borderRadius: BorderRadius.circular(999),
          border: Border.all(color: style.color.withValues(alpha: selected ? 1 : 0.3)),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(style.emoji, style: const TextStyle(fontSize: 13)),
            const SizedBox(width: 5),
            Text(
              purpose.replaceAll('_', ' '),
              style: TextStyle(
                color: selected ? Colors.white : style.color,
                fontWeight: FontWeight.w700,
                fontSize: 12.5,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// Camera-only photo capture (optional) — a slim "Take Photo" bar when
/// empty, a small thumbnail with retake/remove once one's been taken.
class _PhotoField extends StatelessWidget {
  const _PhotoField({required this.photo, required this.onTakePhoto, required this.onRemove});

  final XFile? photo;
  final VoidCallback onTakePhoto;
  final VoidCallback onRemove;

  @override
  Widget build(BuildContext context) {
    if (photo == null) {
      return InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: onTakePhoto,
        child: Container(
          width: double.infinity,
          padding: const EdgeInsets.symmetric(vertical: 10),
          decoration: BoxDecoration(
            border: Border.all(color: AppTheme.brandBlue.withValues(alpha: 0.35), style: BorderStyle.solid),
            borderRadius: BorderRadius.circular(12),
            color: AppTheme.brandBlue.withValues(alpha: 0.05),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.camera_alt_rounded, color: AppTheme.brandBlueDark, size: 20),
              const SizedBox(width: 8),
              Text('Take Photo (optional)', style: TextStyle(color: AppTheme.brandBlueDark, fontWeight: FontWeight.w700, fontSize: 13)),
            ],
          ),
        ),
      );
    }

    return Row(
      children: [
        ClipRRect(
          borderRadius: BorderRadius.circular(12),
          child: Image.file(File(photo!.path), width: 48, height: 48, fit: BoxFit.cover),
        ),
        const SizedBox(width: 14),
        Expanded(
          child: Row(
            children: [
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: onTakePhoto,
                  icon: const Icon(Icons.replay, size: 18),
                  label: const Text('Retake'),
                ),
              ),
              IconButton(
                onPressed: onRemove,
                icon: const Icon(Icons.delete_outline),
                color: Colors.red.shade400,
                tooltip: 'Remove photo',
              ),
            ],
          ),
        ),
      ],
    );
  }
}

/// Blocks in a horizontal strip (like the directory's filter chips); tapping
/// one lists that block's flats right below as chips. The search icon opens
/// the full search sheet (by owner name, too).
class _BlockFlatPicker extends ConsumerStatefulWidget {
  const _BlockFlatPicker({required this.selected, required this.onSelected, required this.onSearch});

  final Flat? selected;
  final ValueChanged<Flat> onSelected;
  final VoidCallback onSearch;

  @override
  ConsumerState<_BlockFlatPicker> createState() => _BlockFlatPickerState();
}

class _BlockFlatPickerState extends ConsumerState<_BlockFlatPicker> {
  static const _noBlock = 'Other';
  String? _block;

  @override
  void didUpdateWidget(covariant _BlockFlatPicker oldWidget) {
    super.didUpdateWidget(oldWidget);
    // A flat picked through search jumps the strip to its block.
    final picked = widget.selected;
    if (picked != null && picked.id != oldWidget.selected?.id) _block = picked.blockName ?? _noBlock;
  }

  @override
  Widget build(BuildContext context) {
    final flats = ref.watch(guardAllFlatsProvider);

    return flats.when(
      loading: () => const Padding(
        padding: EdgeInsets.symmetric(vertical: 20),
        child: Center(child: CircularProgressIndicator()),
      ),
      error: (error, _) => Row(
        children: [
          const Expanded(child: Text("Couldn't load flats.", style: TextStyle(color: Colors.black54))),
          TextButton(onPressed: () => ref.invalidate(guardAllFlatsProvider), child: const Text('Retry')),
        ],
      ),
      data: (items) {
        if (items.isEmpty) return const Text('No flats found.', style: TextStyle(color: Colors.black54));

        final byBlock = <String, List<Flat>>{};
        for (final flat in items) {
          byBlock.putIfAbsent(flat.blockName ?? _noBlock, () => []).add(flat);
        }
        final blocks = byBlock.keys.toList()..sort(_naturalCompare);
        for (final list in byBlock.values) {
          list.sort((a, b) => _naturalCompare(a.flatNumber, b.flatNumber));
        }

        final block = byBlock.containsKey(_block) ? _block! : blocks.first;
        final picked = widget.selected;

        return Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            SizedBox(
              height: 36,
              child: Row(
                children: [
                  Expanded(
                    child: ListView.separated(
                      scrollDirection: Axis.horizontal,
                      itemCount: blocks.length,
                      separatorBuilder: (context, index) => const SizedBox(width: 6),
                      itemBuilder: (context, index) {
                        final name = blocks[index];
                        final isSelected = name == block;

                        return ChoiceChip(
                          label: Text(name),
                          selected: isSelected,
                          showCheckmark: false,
                          visualDensity: VisualDensity.compact,
                          selectedColor: AppTheme.brandBlue,
                          labelStyle: TextStyle(
                            fontWeight: FontWeight.w700,
                            fontSize: 12.5,
                            color: isSelected ? Colors.white : AppTheme.brandBlueDark,
                          ),
                          onSelected: (_) => setState(() => _block = name),
                        );
                      },
                    ),
                  ),
                  IconButton(
                    tooltip: 'Search flat or owner',
                    visualDensity: VisualDensity.compact,
                    icon: const Icon(Icons.search_rounded, color: AppTheme.brandBlueDark),
                    onPressed: widget.onSearch,
                  ),
                ],
              ),
            ),
            const SizedBox(height: 8),
            // Capped so a big block never pushes the rest of the form off screen.
            ConstrainedBox(
              constraints: const BoxConstraints(maxHeight: 120),
              child: SingleChildScrollView(
                child: Wrap(
                  spacing: 6,
                  runSpacing: 6,
                  children: [
                    for (final flat in byBlock[block]!)
                      _FlatChip(
                        flat: flat,
                        selected: flat.id == picked?.id,
                        onTap: () => widget.onSelected(flat),
                      ),
                  ],
                ),
              ),
            ),
            if (picked != null) ...[
              const SizedBox(height: 6),
              Text(
                [picked.displayLabel, if (picked.ownerName != null) picked.ownerName!].join(' · '),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: AppTheme.brandBlueDark),
              ),
            ],
          ],
        );
      },
    );
  }
}

/// Orders "A-2" before "A-10", and "Block 2" before "Block 10".
int _naturalCompare(String a, String b) {
  final pattern = RegExp(r'(\d+)|(\D+)');
  final pa = pattern.allMatches(a.toLowerCase()).map((m) => m.group(0)!).toList();
  final pb = pattern.allMatches(b.toLowerCase()).map((m) => m.group(0)!).toList();
  for (var i = 0; i < pa.length && i < pb.length; i++) {
    final na = int.tryParse(pa[i]);
    final nb = int.tryParse(pb[i]);
    final c = (na != null && nb != null) ? na.compareTo(nb) : pa[i].compareTo(pb[i]);
    if (c != 0) return c;
  }
  return pa.length.compareTo(pb.length);
}

class _FlatChip extends StatelessWidget {
  const _FlatChip({required this.flat, required this.selected, required this.onTap});

  final Flat flat;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    // House Closed: shown in red so the guard sees it before sending.
    final color = flat.houseClosed ? Colors.red.shade400 : AppTheme.brandBlue;

    return InkWell(
      borderRadius: BorderRadius.circular(10),
      onTap: onTap,
      child: Container(
        constraints: const BoxConstraints(minWidth: 56),
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
        decoration: BoxDecoration(
          color: selected ? color : color.withValues(alpha: 0.07),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: color.withValues(alpha: selected ? 1 : 0.3)),
        ),
        child: Text(
          flat.flatNumber,
          textAlign: TextAlign.center,
          style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13, color: selected ? Colors.white : color),
        ),
      ),
    );
  }
}

/// Full-screen-ish sheet: big search box + big tappable rows, so a guard can
/// find a flat with a thumb in a couple of taps instead of typing carefully.
class _FlatPickerSheet extends ConsumerWidget {
  const _FlatPickerSheet();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final flats = ref.watch(guardFlatListProvider);

    return FractionallySizedBox(
      heightFactor: 0.85,
      child: DecoratedBox(
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: SafeArea(
          top: false,
          child: Column(
            children: [
              const SizedBox(height: 10),
              Container(width: 40, height: 4, decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2))),
              const Padding(
                padding: EdgeInsets.fromLTRB(20, 16, 20, 12),
                child: Row(
                  children: [
                    Expanded(child: Text('Select Flat', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700))),
                  ],
                ),
              ),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: TextField(
                  autofocus: true,
                  decoration: InputDecoration(
                    hintText: 'Search by flat number, owner or block',
                    prefixIcon: const Icon(Icons.search),
                    filled: true,
                    fillColor: const Color(0xFFF2F3F7),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                  ),
                  onChanged: (value) => ref.read(guardFlatSearchProvider.notifier).state = value,
                ),
              ),
              const SizedBox(height: 8),
              Expanded(
                child: AsyncView<List<Flat>>(
                  value: flats,
                  onRetry: () => ref.invalidate(guardFlatListProvider),
                  builder: (context, items) {
                    if (items.isEmpty) {
                      return const EmptyState(message: 'No matching flat found.', icon: Icons.home_outlined);
                    }

                    return ListView.builder(
                      padding: const EdgeInsets.fromLTRB(12, 0, 12, 12),
                      itemCount: items.length,
                      itemBuilder: (context, index) {
                        final flat = items[index];

                        return ListTile(
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          leading: CircleAvatar(
                            backgroundColor: AppTheme.brandBlue.withValues(alpha: 0.12),
                            child: const Icon(Icons.home_rounded, color: AppTheme.brandBlueDark),
                          ),
                          title: Text(flat.displayLabel, style: const TextStyle(fontWeight: FontWeight.w700)),
                          subtitle: flat.ownerName != null ? Text(flat.ownerName!) : null,
                          onTap: () => Navigator.of(context).pop(flat),
                        );
                      },
                    );
                  },
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

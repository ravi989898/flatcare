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
/// but the flat picker searches the whole society via a full-screen sheet
/// (Api\V1\Guard\FlatController) instead of the resident's own flat(s) —
/// tapping through a big list is faster at a gate than typing while
/// someone's waiting — and submitting lands the visitor already checked_in
/// instead of pending.
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
  final _notesController = TextEditingController();
  String _purpose = Visitor.purposes.first;
  Flat? _flat;
  XFile? _photo;
  bool _isSubmitting = false;
  bool _requiresApproval = false;

  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    _vehicleController.dispose();
    _notesController.dispose();
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
            notes: _notesController.text.trim(),
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
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF2F3F7),
      appBar: AppBar(
        title: const Text('Walk-in Check-in'),
        foregroundColor: Colors.white,
        flexibleSpace: const DecoratedBox(decoration: BoxDecoration(gradient: AppTheme.brandGradient)),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                _SectionCard(
                  title: 'Which flat?',
                  child: _FlatField(flat: _flat, onTap: _pickFlat),
                ),
                const SizedBox(height: 14),
                _SectionCard(
                  title: 'Visitor photo (optional)',
                  child: _PhotoField(photo: _photo, onTakePhoto: _takePhoto, onRemove: () => setState(() => _photo = null)),
                ),
                const SizedBox(height: 14),
                _SectionCard(
                  title: 'Visitor details',
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      TextFormField(
                        controller: _nameController,
                        decoration: const InputDecoration(labelText: 'Visitor Name', prefixIcon: Icon(Icons.badge_outlined)),
                        validator: (value) => (value == null || value.trim().isEmpty) ? "Enter the visitor's name" : null,
                      ),
                      const SizedBox(height: 14),
                      TextFormField(
                        controller: _phoneController,
                        keyboardType: TextInputType.phone,
                        decoration: const InputDecoration(labelText: 'Phone (optional)', prefixIcon: Icon(Icons.phone_outlined)),
                      ),
                      const SizedBox(height: 14),
                      TextFormField(
                        controller: _vehicleController,
                        decoration:
                            const InputDecoration(labelText: 'Vehicle Number (optional)', prefixIcon: Icon(Icons.directions_car_outlined)),
                      ),
                      const SizedBox(height: 14),
                      TextFormField(
                        controller: _notesController,
                        decoration: const InputDecoration(labelText: 'Notes (optional)', prefixIcon: Icon(Icons.notes_outlined)),
                        maxLines: 2,
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 14),
                _SectionCard(
                  title: 'Purpose of visit',
                  child: Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      for (final purpose in Visitor.purposes) _PurposeChip(
                        purpose: purpose,
                        selected: purpose == _purpose,
                        onTap: () => setState(() => _purpose = purpose),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 14),
                _SectionCard(
                  title: 'Entry',
                  child: SwitchListTile.adaptive(
                    contentPadding: EdgeInsets.zero,
                    value: _requiresApproval,
                    onChanged: (value) => setState(() => _requiresApproval = value),
                    title: const Text('Ask resident to approve', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
                    subtitle: const Text(
                      "Waits for the resident's approval instead of letting the visitor in right away.",
                      style: TextStyle(fontSize: 12.5),
                    ),
                  ),
                ),
                const SizedBox(height: 24),
                DecoratedBox(
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(28),
                    gradient: (_isSubmitting || _flat == null) ? null : AppTheme.brandGradient,
                    color: (_isSubmitting || _flat == null) ? Colors.grey.shade300 : null,
                  ),
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.transparent,
                      shadowColor: Colors.transparent,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(28)),
                    ),
                    onPressed: (_isSubmitting || _flat == null) ? null : _submit,
                    child: _isSubmitting
                        ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                        : Text(_requiresApproval ? 'Send Request' : 'Check In', style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
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

class _SectionCard extends StatelessWidget {
  const _SectionCard({required this.title, required this.child});

  final String title;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 8, offset: const Offset(0, 2))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w700, color: AppTheme.brandBlueDark, letterSpacing: .2),
          ),
          const SizedBox(height: 12),
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
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
        decoration: BoxDecoration(
          color: selected ? style.color : style.color.withValues(alpha: 0.1),
          borderRadius: BorderRadius.circular(999),
          border: Border.all(color: style.color.withValues(alpha: selected ? 1 : 0.3)),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(style.emoji, style: const TextStyle(fontSize: 15)),
            const SizedBox(width: 6),
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

/// Camera-only photo capture — a big "Take Photo" tap target when empty,
/// a thumbnail with a retake/remove overlay once one's been taken.
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
          padding: const EdgeInsets.symmetric(vertical: 20),
          decoration: BoxDecoration(
            border: Border.all(color: AppTheme.brandBlue.withValues(alpha: 0.35), style: BorderStyle.solid),
            borderRadius: BorderRadius.circular(12),
            color: AppTheme.brandBlue.withValues(alpha: 0.05),
          ),
          child: Column(
            children: [
              const Icon(Icons.camera_alt_rounded, color: AppTheme.brandBlueDark, size: 28),
              const SizedBox(height: 8),
              Text('Take Photo', style: TextStyle(color: AppTheme.brandBlueDark, fontWeight: FontWeight.w700)),
            ],
          ),
        ),
      );
    }

    return Row(
      children: [
        ClipRRect(
          borderRadius: BorderRadius.circular(12),
          child: Image.file(File(photo!.path), width: 72, height: 72, fit: BoxFit.cover),
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

/// Tap target that opens the flat-picker sheet — looks like a form field so
/// it sits naturally among the real TextFormFields around it.
class _FlatField extends StatelessWidget {
  const _FlatField({required this.flat, required this.onTap});

  final Flat? flat;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(12),
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
        decoration: BoxDecoration(
          border: Border.all(color: flat == null ? Colors.red.shade300 : AppTheme.brandBlue.withValues(alpha: 0.4)),
          borderRadius: BorderRadius.circular(12),
          color: flat == null ? Colors.red.withValues(alpha: 0.03) : AppTheme.brandBlue.withValues(alpha: 0.05),
        ),
        child: Row(
          children: [
            Icon(Icons.home_rounded, color: flat == null ? Colors.red.shade300 : AppTheme.brandBlueDark),
            const SizedBox(width: 12),
            Expanded(
              child: flat == null
                  ? const Text('Tap to select the flat being visited', style: TextStyle(color: Colors.black54))
                  : Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(flat!.displayLabel, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
                        if (flat!.ownerName != null)
                          Text(flat!.ownerName!, style: TextStyle(color: Colors.grey.shade600, fontSize: 12.5)),
                      ],
                    ),
            ),
            const Icon(Icons.chevron_right, color: Colors.black38),
          ],
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

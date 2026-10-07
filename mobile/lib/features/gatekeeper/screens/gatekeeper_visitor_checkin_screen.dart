import 'dart:io';
import 'dart:ui' show PathMetric;

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';

import '../../../core/api/api_exception.dart';
import '../../../core/models/flat.dart';
import '../../../core/theme/app_theme.dart';
import '../../visitors/data/visitor.dart';
import '../data/guard_visitor_repository.dart';
import '../providers/gatekeeper_providers.dart';
import '../widgets/purpose_style.dart';

const _ink = Color(0xFF0B1E3D);
const _fieldBorder = Color(0xFFD9DEE7);

/// Walk-in check-in — a visitor who showed up without a resident's
/// pre-invite. The guard picks any flat in the society
/// (Api\V1\Guard\FlatController): tap a block in the horizontal strip, then
/// pick its flat from the "Flat Number" dropdown. The entry is sent to the
/// resident for approval. Check In is pinned at the bottom.
class GatekeeperVisitorCheckinScreen extends ConsumerStatefulWidget {
  const GatekeeperVisitorCheckinScreen({super.key});

  @override
  ConsumerState<GatekeeperVisitorCheckinScreen> createState() => _GatekeeperVisitorCheckinScreenState();
}

class _GatekeeperVisitorCheckinScreenState extends ConsumerState<GatekeeperVisitorCheckinScreen> {
  static const _noBlock = 'Other';
  static const _countryCodes = ['+91', '+1', '+44', '+971', '+977', '+61'];

  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _phoneController = TextEditingController();
  final _vehicleController = TextEditingController();
  String _purpose = Visitor.purposes.first;
  String _countryCode = _countryCodes.first;
  String? _block;
  Flat? _flat;
  XFile? _photo;
  bool _isSubmitting = false;

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

  Future<void> _pickFlat(List<Flat> flats) async {
    final flat = await showModalBottomSheet<Flat>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => _FlatSheet(flats: flats, selected: _flat),
    );
    if (flat != null) setState(() => _flat = flat);
  }

  Future<void> _submit() async {
    final formValid = _formKey.currentState!.validate();
    if (_flat == null) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Select a flat number.')));
      return;
    }
    if (!formValid) return;

    // Indian numbers are stored as plain 10 digits, like everywhere else in
    // the app; any other country keeps its code.
    final digits = _phoneController.text.trim();
    final phone = _countryCode == '+91' ? digits : '$_countryCode $digits';

    setState(() => _isSubmitting = true);
    try {
      await ref.read(guardVisitorRepositoryProvider).checkInWalkIn(
            flatId: _flat!.id,
            visitorName: _nameController.text.trim(),
            visitorPhone: phone,
            purpose: _purpose,
            vehicleNumber: _vehicleController.text.trim(),
            photoPath: _photo?.path,
          );
      ref.invalidate(guardVisitorListProvider);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Entry request sent to ${_flat!.displayLabel} for approval.')),
      );
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
    final flats = ref.watch(guardAllFlatsProvider);

    return Scaffold(
      backgroundColor: const Color(0xFFF4F7FB),
      appBar: AppBar(
        backgroundColor: const Color(0xFFF4F7FB),
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        centerTitle: true,
        leadingWidth: 64,
        leading: Padding(
          padding: const EdgeInsets.only(left: 12),
          child: Center(
            child: Material(
              color: const Color(0xFFE6EBF2),
              shape: const CircleBorder(),
              child: InkWell(
                customBorder: const CircleBorder(),
                onTap: () => context.pop(),
                child: const SizedBox(
                  width: 42,
                  height: 42,
                  child: Icon(Icons.chevron_left_rounded, color: AppTheme.brandBlueDark, size: 28),
                ),
              ),
            ),
          ),
        ),
        title: const Text(
          'Walk-in Check-in',
          style: TextStyle(color: _ink, fontWeight: FontWeight.w800, fontSize: 19),
        ),
      ),
      body: SafeArea(
        top: false,
        child: Column(
          children: [
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.fromLTRB(14, 4, 14, 8),
                child: Form(
                  key: _formKey,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      _SectionCard(child: _flatSection(flats)),
                      const SizedBox(height: 12),
                      _SectionCard(child: _visitorSection()),
                      const SizedBox(height: 12),
                      _SectionCard(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            const _SectionTitle(icon: Icons.assignment_turned_in_rounded, title: 'Purpose of Visit'),
                            const SizedBox(height: 12),
                            _PurposeGrid(
                              selected: _purpose,
                              onSelected: (purpose) => setState(() => _purpose = purpose),
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
              padding: const EdgeInsets.fromLTRB(14, 4, 14, 12),
              child: _CheckInButton(isSubmitting: _isSubmitting, onPressed: _isSubmitting ? null : _submit),
            ),
          ],
        ),
      ),
    );
  }

  Widget _flatSection(AsyncValue<List<Flat>> flats) {
    return flats.when(
      loading: () => const Padding(
        padding: EdgeInsets.symmetric(vertical: 24),
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

        return Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const _SectionTitle(icon: Icons.apartment_rounded, title: 'Select Block'),
            const SizedBox(height: 12),
            _BlockStrip(
              blocks: blocks,
              selected: block,
              onSelected: (name) => setState(() {
                _block = name;
                if (_flat != null && (_flat!.blockName ?? _noBlock) != name) _flat = null;
              }),
            ),
            const SizedBox(height: 18),
            const _SectionTitle(icon: Icons.home_outlined, title: 'Flat Number', iconColor: _ink),
            const SizedBox(height: 10),
            _FlatDropdown(flat: _flat, onTap: () => _pickFlat(byBlock[block]!)),
          ],
        );
      },
    );
  }

  Widget _visitorSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        const _SectionTitle(icon: Icons.person_rounded, title: 'Visitor Details'),
        const SizedBox(height: 12),
        _PhotoField(photo: _photo, onTakePhoto: _takePhoto, onRemove: () => setState(() => _photo = null)),
        const SizedBox(height: 12),
        _FieldBox(
          icon: Icons.person_outline_rounded,
          label: 'Visitor Name',
          required: true,
          child: TextFormField(
            controller: _nameController,
            textCapitalization: TextCapitalization.words,
            decoration: _bareInput('Enter visitor name'),
            validator: (value) => (value == null || value.trim().isEmpty) ? "Enter the visitor's name" : null,
          ),
        ),
        const SizedBox(height: 10),
        _FieldBox(
          icon: Icons.phone_outlined,
          label: 'Phone Number',
          required: true,
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Padding(
                padding: const EdgeInsets.only(top: 6),
                child: _CountryCodePicker(
                  value: _countryCode,
                  codes: _countryCodes,
                  onChanged: (code) => setState(() => _countryCode = code),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: TextFormField(
                  controller: _phoneController,
                  keyboardType: TextInputType.phone,
                  inputFormatters: [FilteringTextInputFormatter.digitsOnly, LengthLimitingTextInputFormatter(15)],
                  decoration: _bareInput('Enter phone number').copyWith(contentPadding: const EdgeInsets.only(top: 14, bottom: 6)),
                  validator: (value) {
                    final digits = value?.trim() ?? '';
                    if (digits.isEmpty) return 'Enter the phone number';
                    if (_countryCode == '+91' ? digits.length != 10 : digits.length < 6) return 'Enter a valid phone number';
                    return null;
                  },
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 10),
        _FieldBox(
          icon: Icons.directions_car_outlined,
          label: 'Vehicle Number',
          child: TextFormField(
            controller: _vehicleController,
            textCapitalization: TextCapitalization.characters,
            decoration: _bareInput('e.g. GJ01AB1234'),
          ),
        ),
      ],
    );
  }

  static InputDecoration _bareInput(String hint) => InputDecoration(
        hintText: hint,
        hintStyle: const TextStyle(color: Color(0xFF8A94A6), fontSize: 15),
        isDense: true,
        filled: false,
        contentPadding: const EdgeInsets.only(top: 6, bottom: 2),
        border: InputBorder.none,
        enabledBorder: InputBorder.none,
        focusedBorder: InputBorder.none,
        errorBorder: InputBorder.none,
        focusedErrorBorder: InputBorder.none,
      );
}

class _SectionCard extends StatelessWidget {
  const _SectionCard({required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.fromLTRB(14, 16, 14, 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 10, offset: const Offset(0, 3))],
      ),
      child: child,
    );
  }
}

class _SectionTitle extends StatelessWidget {
  const _SectionTitle({required this.icon, required this.title, this.iconColor = AppTheme.brandBlue});

  final IconData icon;
  final String title;
  final Color iconColor;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Icon(icon, color: iconColor, size: 24),
        const SizedBox(width: 10),
        Text(title, style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w800, color: _ink)),
      ],
    );
  }
}

/// Horizontally scrolling block chips, with a "next" arrow and a thin
/// progress bar showing how far along the strip the guard has scrolled.
class _BlockStrip extends StatefulWidget {
  const _BlockStrip({required this.blocks, required this.selected, required this.onSelected});

  final List<String> blocks;
  final String selected;
  final ValueChanged<String> onSelected;

  @override
  State<_BlockStrip> createState() => _BlockStripState();
}

class _BlockStripState extends State<_BlockStrip> {
  final _controller = ScrollController();

  @override
  void initState() {
    super.initState();
    // Scroll extents are only known after the first layout.
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) setState(() {});
    });
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _scrollForward() {
    final position = _controller.position;
    final target = (position.pixels + position.viewportDimension * 0.7).clamp(0.0, position.maxScrollExtent);
    _controller.animateTo(target, duration: const Duration(milliseconds: 300), curve: Curves.easeOut);
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _controller,
      builder: (context, _) {
        final position = _controller.hasClients && _controller.position.hasContentDimensions ? _controller.position : null;
        final scrollable = position != null && position.maxScrollExtent > 0;
        final atEnd = !scrollable || position.pixels >= position.maxScrollExtent - 1;
        final progress = scrollable
            ? ((position.pixels + position.viewportDimension) / (position.maxScrollExtent + position.viewportDimension)).clamp(0.0, 1.0)
            : 1.0;

        return Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            SizedBox(
              height: 46,
              child: Stack(
                alignment: Alignment.centerRight,
                children: [
                  ListView.separated(
                    controller: _controller,
                    scrollDirection: Axis.horizontal,
                    padding: EdgeInsets.only(right: atEnd ? 0 : 40),
                    itemCount: widget.blocks.length,
                    separatorBuilder: (context, index) => const SizedBox(width: 8),
                    itemBuilder: (context, index) {
                      final name = widget.blocks[index];
                      return _BlockChip(label: name, selected: name == widget.selected, onTap: () => widget.onSelected(name));
                    },
                  ),
                  if (!atEnd)
                    Material(
                      color: Colors.white,
                      shape: const CircleBorder(),
                      elevation: 2,
                      shadowColor: Colors.black26,
                      child: InkWell(
                        customBorder: const CircleBorder(),
                        onTap: _scrollForward,
                        child: const SizedBox(
                          width: 34,
                          height: 34,
                          child: Icon(Icons.chevron_right_rounded, color: _ink),
                        ),
                      ),
                    ),
                ],
              ),
            ),
            if (scrollable) ...[
              const SizedBox(height: 10),
              Padding(
                padding: const EdgeInsets.only(right: 40),
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(4),
                  child: LinearProgressIndicator(
                    value: progress,
                    minHeight: 6,
                    backgroundColor: const Color(0xFFE3E8EF),
                    valueColor: const AlwaysStoppedAnimation(AppTheme.brandBlueDark),
                  ),
                ),
              ),
            ],
          ],
        );
      },
    );
  }
}

class _BlockChip extends StatelessWidget {
  const _BlockChip({required this.label, required this.selected, required this.onTap});

  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(12),
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        constraints: const BoxConstraints(minWidth: 96),
        padding: const EdgeInsets.symmetric(horizontal: 18),
        alignment: Alignment.center,
        decoration: BoxDecoration(
          gradient: selected ? AppTheme.brandGradient : null,
          color: selected ? null : const Color(0xFFF7F9FC),
          borderRadius: BorderRadius.circular(12),
          border: selected ? null : Border.all(color: _fieldBorder),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 15,
            fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
            color: selected ? Colors.white : _ink,
          ),
        ),
      ),
    );
  }
}

class _FlatDropdown extends StatelessWidget {
  const _FlatDropdown({required this.flat, required this.onTap});

  final Flat? flat;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final picked = flat;
    final label = picked == null
        ? 'Select Flat Number'
        : [picked.displayLabel, if (picked.ownerName != null) picked.ownerName!].join(' · ');

    return InkWell(
      borderRadius: BorderRadius.circular(12),
      onTap: onTap,
      child: Container(
        height: 56,
        padding: const EdgeInsets.symmetric(horizontal: 16),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: _fieldBorder),
        ),
        child: Row(
          children: [
            const Icon(Icons.domain_rounded, color: AppTheme.brandBlueDark, size: 26),
            const SizedBox(width: 16),
            Expanded(
              child: Text(
                label,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  fontSize: 15,
                  color: picked == null ? const Color(0xFF5B6472) : _ink,
                  fontWeight: picked == null ? FontWeight.w400 : FontWeight.w700,
                ),
              ),
            ),
            const Icon(Icons.keyboard_arrow_down_rounded, color: Color(0xFF5B6472)),
          ],
        ),
      ),
    );
  }
}

/// The selected block's flats as a tappable grid. House Closed flats show in
/// red so the guard sees it before sending.
class _FlatSheet extends StatelessWidget {
  const _FlatSheet({required this.flats, required this.selected});

  final List<Flat> flats;
  final Flat? selected;

  @override
  Widget build(BuildContext context) {
    return ConstrainedBox(
      constraints: BoxConstraints(maxHeight: MediaQuery.sizeOf(context).height * 0.75),
      child: DecoratedBox(
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: SafeArea(
          top: false,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const SizedBox(height: 10),
              Center(
                child: Container(width: 40, height: 4, decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2))),
              ),
              const Padding(
                padding: EdgeInsets.fromLTRB(20, 16, 20, 12),
                child: Text('Select Flat Number', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: _ink)),
              ),
              Flexible(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
                  child: Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      for (final flat in flats)
                        _FlatChip(
                          flat: flat,
                          selected: flat.id == selected?.id,
                          onTap: () => Navigator.of(context).pop(flat),
                        ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _FlatChip extends StatelessWidget {
  const _FlatChip({required this.flat, required this.selected, required this.onTap});

  final Flat flat;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final color = flat.houseClosed ? Colors.red.shade400 : AppTheme.brandBlue;

    return InkWell(
      borderRadius: BorderRadius.circular(12),
      onTap: onTap,
      child: Container(
        constraints: const BoxConstraints(minWidth: 72),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        decoration: BoxDecoration(
          color: selected ? color : color.withValues(alpha: 0.07),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: color.withValues(alpha: selected ? 1 : 0.3)),
        ),
        child: Text(
          flat.flatNumber,
          textAlign: TextAlign.center,
          style: TextStyle(fontWeight: FontWeight.w700, fontSize: 15, color: selected ? Colors.white : color),
        ),
      ),
    );
  }
}

/// Camera-only photo capture (optional) — a dashed "Take Photo" card when
/// empty, the thumbnail with retake/remove once one's been taken.
class _PhotoField extends StatelessWidget {
  const _PhotoField({required this.photo, required this.onTakePhoto, required this.onRemove});

  final XFile? photo;
  final VoidCallback onTakePhoto;
  final VoidCallback onRemove;

  @override
  Widget build(BuildContext context) {
    final taken = photo;

    return CustomPaint(
      foregroundPainter: _DashedBorderPainter(color: AppTheme.brandBlue.withValues(alpha: 0.6), radius: 14),
      child: Material(
        color: AppTheme.brandBlue.withValues(alpha: 0.05),
        borderRadius: BorderRadius.circular(14),
        child: InkWell(
          borderRadius: BorderRadius.circular(14),
          onTap: onTakePhoto,
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            child: Row(
              children: [
                if (taken == null)
                  Container(
                    width: 72,
                    height: 72,
                    decoration: BoxDecoration(color: AppTheme.brandBlue.withValues(alpha: 0.12), shape: BoxShape.circle),
                    child: const Icon(Icons.photo_camera_outlined, color: AppTheme.brandBlueDark, size: 32),
                  )
                else
                  ClipOval(child: Image.file(File(taken.path), width: 72, height: 72, fit: BoxFit.cover)),
                const SizedBox(width: 18),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        taken == null ? 'Take Photo (Optional)' : 'Photo added',
                        style: const TextStyle(color: AppTheme.brandBlueDark, fontWeight: FontWeight.w700, fontSize: 16),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        taken == null ? 'Add visitor photo for identification' : 'Tap to retake',
                        style: const TextStyle(color: Color(0xFF5B6472), fontSize: 13),
                      ),
                    ],
                  ),
                ),
                if (taken != null)
                  IconButton(
                    onPressed: onRemove,
                    icon: const Icon(Icons.delete_outline),
                    color: Colors.red.shade400,
                    tooltip: 'Remove photo',
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _DashedBorderPainter extends CustomPainter {
  const _DashedBorderPainter({required this.color, required this.radius});

  final Color color;
  final double radius;

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = color
      ..style = PaintingStyle.stroke
      ..strokeWidth = 1.2;
    final path = Path()..addRRect(RRect.fromRectAndRadius(Offset.zero & size, Radius.circular(radius)));
    for (final PathMetric metric in path.computeMetrics()) {
      for (double d = 0; d < metric.length; d += 9) {
        canvas.drawPath(metric.extractPath(d, d + 5), paint);
      }
    }
  }

  @override
  bool shouldRepaint(_DashedBorderPainter oldDelegate) => oldDelegate.color != color || oldDelegate.radius != radius;
}

/// Outlined box with a leading icon and a label (red * when required) above
/// the borderless input.
class _FieldBox extends StatelessWidget {
  const _FieldBox({required this.icon, required this.label, required this.child, this.required = false});

  final IconData icon;
  final String label;
  final Widget child;
  final bool required;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 10),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: _fieldBorder),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.only(top: 12),
            child: Icon(icon, color: const Color(0xFF3B4556), size: 26),
          ),
          const SizedBox(width: 18),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text.rich(
                  TextSpan(
                    text: label,
                    children: [if (required) const TextSpan(text: ' *', style: TextStyle(color: Colors.red))],
                  ),
                  style: const TextStyle(fontSize: 14.5, fontWeight: FontWeight.w600, color: _ink),
                ),
                child,
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _CountryCodePicker extends StatelessWidget {
  const _CountryCodePicker({required this.value, required this.codes, required this.onChanged});

  final String value;
  final List<String> codes;
  final ValueChanged<String> onChanged;

  @override
  Widget build(BuildContext context) {
    return PopupMenuButton<String>(
      initialValue: value,
      onSelected: onChanged,
      itemBuilder: (context) => [for (final code in codes) PopupMenuItem(value: code, child: Text(code))],
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        decoration: BoxDecoration(
          color: const Color(0xFFF2F5F9),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: _fieldBorder),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(value, style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w600, color: _ink)),
            const SizedBox(width: 6),
            const Icon(Icons.keyboard_arrow_down_rounded, size: 20, color: Color(0xFF5B6472)),
          ],
        ),
      ),
    );
  }
}

class _PurposeGrid extends StatelessWidget {
  const _PurposeGrid({required this.selected, required this.onSelected});

  final String selected;
  final ValueChanged<String> onSelected;

  @override
  Widget build(BuildContext context) {
    const columns = 3;
    const gap = 10.0;

    return LayoutBuilder(
      builder: (context, constraints) {
        final width = (constraints.maxWidth - gap * (columns - 1)) / columns;

        return Wrap(
          spacing: gap,
          runSpacing: gap,
          children: [
            for (final purpose in Visitor.purposes)
              SizedBox(
                width: width,
                child: _PurposeChip(purpose: purpose, selected: purpose == selected, onTap: () => onSelected(purpose)),
              ),
          ],
        );
      },
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
      borderRadius: BorderRadius.circular(14),
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        height: 46,
        padding: const EdgeInsets.symmetric(horizontal: 8),
        decoration: BoxDecoration(
          color: selected ? style.color : style.color.withValues(alpha: 0.08),
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: style.color.withValues(alpha: selected ? 1 : 0.35)),
        ),
        child: Row(
          children: [
            Text(style.emoji, style: const TextStyle(fontSize: 18)),
            const SizedBox(width: 6),
            Expanded(
              child: Text(
                style.label,
                textAlign: TextAlign.center,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  color: selected ? Colors.white : style.color,
                  fontWeight: FontWeight.w700,
                  fontSize: 14.5,
                ),
              ),
            ),
            if (selected) const Icon(Icons.check_circle_rounded, color: Colors.white, size: 20),
          ],
        ),
      ),
    );
  }
}

class _CheckInButton extends StatelessWidget {
  const _CheckInButton({required this.isSubmitting, required this.onPressed});

  final bool isSubmitting;
  final VoidCallback? onPressed;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(18),
        gradient: AppTheme.brandGradient,
        boxShadow: [BoxShadow(color: AppTheme.brandBlue.withValues(alpha: 0.3), blurRadius: 12, offset: const Offset(0, 5))],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(18),
          onTap: onPressed,
          child: SizedBox(
            height: 58,
            child: isSubmitting
                ? const Center(child: SizedBox(height: 22, width: 22, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)))
                : const Stack(
                    alignment: Alignment.center,
                    children: [
                      Text('Check In', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 18)),
                      Positioned(right: 20, child: Icon(Icons.arrow_forward_rounded, color: Colors.white, size: 26)),
                    ],
                  ),
          ),
        ),
      ),
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

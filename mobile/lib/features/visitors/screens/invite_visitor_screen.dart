import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/api/api_exception.dart';
import '../../../core/models/flat.dart';
import '../data/visitor.dart';
import '../data/visitor_repository.dart';
import '../providers/visitor_providers.dart';
import '../widgets/flat_picker.dart';
import '../widgets/visitor_ui.dart';

/// "Gate Pass" — a resident pre-approves a visitor for a From/To window and
/// gets a shareable pass code back (see StoreVisitorInviteRequest on the
/// backend; the quicker Pre-Approval form uses the same endpoint).
class InviteVisitorScreen extends ConsumerStatefulWidget {
  const InviteVisitorScreen({super.key});

  @override
  ConsumerState<InviteVisitorScreen> createState() => _InviteVisitorScreenState();
}

class _InviteVisitorScreenState extends ConsumerState<InviteVisitorScreen> {
  static const _purposeOptions = ['Family visit', 'Meeting', 'Delivery', 'Maintenance work', 'Interview', 'Other'];

  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _phoneController = TextEditingController();
  final _emailController = TextEditingController();

  String? _type;
  String? _purposeNote;
  DateTime? _from;
  DateTime? _to;
  Flat? _flat;
  String? _photoPath;
  bool _isSubmitting = false;

  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    _emailController.dispose();
    super.dispose();
  }

  Future<DateTime?> _pickDate({required DateTime initial, required DateTime first}) {
    return showDatePicker(
      context: context,
      initialDate: initial.isBefore(first) ? first : initial,
      firstDate: first,
      lastDate: DateTime.now().add(const Duration(days: 365)),
      builder: (context, child) => Theme(
        data: Theme.of(context).copyWith(colorScheme: Theme.of(context).colorScheme.copyWith(primary: VisitorColors.primary)),
        child: child!,
      ),
    );
  }

  Future<void> _submit(Flat? flat) async {
    if (!_formKey.currentState!.validate() || flat == null) return;

    setState(() => _isSubmitting = true);
    try {
      final visitor = await ref.read(visitorRepositoryProvider).invite(
            flatId: flat.id,
            visitorName: _nameController.text.trim(),
            visitorPhone: _phoneController.text.trim(),
            visitorEmail: _emailController.text.trim(),
            purpose: _type!,
            kind: 'gate_pass',
            from: _from,
            to: _to,
            notes: _purposeNote,
            photoPath: _photoPath,
          );
      ref.invalidate(visitorListProvider);
      if (!mounted) return;
      showVisitorSnack(context, 'Gate pass created');
      context.pushReplacement('/visitors/gate-pass', extra: visitor);
    } on ApiException catch (e) {
      if (mounted) showVisitorSnack(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final flats = ref.watch(residentFlatsProvider);
    final flat = _flat ?? (flats.isNotEmpty ? flats.first : null);
    final today = DateTime.now();
    final todayDate = DateTime(today.year, today.month, today.day);

    return VisitorScaffold(
      title: 'Gate Pass',
      bottom: VisitorPrimaryButton(
        label: 'Save Gate Pass',
        loading: _isSubmitting,
        onPressed: flat == null ? null : () => _submit(flat),
      ),
      body: SingleChildScrollView(
        keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
        padding: const EdgeInsets.fromLTRB(20, 20, 20, 24),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              VisitorPhotoPicker(path: _photoPath, onChanged: (path) => setState(() => _photoPath = path)),
              const SizedBox(height: 20),
              FlatPicker(flats: flats, value: flat, onChanged: (value) => setState(() => _flat = value)),
              VisitorTextField(
                label: 'Visitor Name',
                hint: 'Enter visitor name',
                icon: Icons.person_rounded,
                controller: _nameController,
                required: true,
              ),
              const SizedBox(height: 16),
              VisitorTextField(
                label: 'Mobile Number',
                hint: 'Enter mobile number',
                icon: Icons.call_rounded,
                controller: _phoneController,
                keyboardType: TextInputType.phone,
                maxLength: 15,
                required: true,
                validator: (value) {
                  final digits = (value ?? '').replaceAll(RegExp(r'\D'), '');
                  if (digits.isEmpty) return 'Enter the mobile number';
                  if (digits.length < 10) return 'Enter a valid mobile number';
                  return null;
                },
              ),
              const SizedBox(height: 16),
              VisitorDropdown<String>(
                label: 'Type',
                hint: 'Select Type',
                icon: Icons.category_rounded,
                iconColor: const Color(0xFF8E5FE0),
                required: true,
                value: _type,
                items: Visitor.purposes.map((p) => DropdownMenuItem(value: p, child: Text(purposeLabel(p)))).toList(),
                onChanged: (value) => setState(() => _type = value),
              ),
              const SizedBox(height: 16),
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: VisitorDateField(
                      key: ValueKey('from-$_from'),
                      label: 'From',
                      required: true,
                      value: _from,
                      onPick: () async {
                        final picked = await _pickDate(initial: _from ?? todayDate, first: todayDate);
                        if (picked != null) {
                          setState(() {
                            _from = picked;
                            if (_to != null && _to!.isBefore(picked)) _to = picked;
                          });
                        }
                        return picked;
                      },
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: VisitorDateField(
                      key: ValueKey('to-$_to-$_from'),
                      label: 'To',
                      required: true,
                      value: _to,
                      onPick: () async {
                        final first = _from ?? todayDate;
                        final picked = await _pickDate(initial: _to ?? first, first: first);
                        if (picked != null) setState(() => _to = picked);
                        return picked;
                      },
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              VisitorTextField(
                label: 'Email (Optional)',
                hint: 'Enter email address',
                icon: Icons.email_rounded,
                controller: _emailController,
                keyboardType: TextInputType.emailAddress,
                validator: (value) {
                  final email = (value ?? '').trim();
                  if (email.isEmpty) return null;
                  return RegExp(r'^[^@\s]+@[^@\s]+\.[^@\s]+$').hasMatch(email) ? null : 'Enter a valid email address';
                },
              ),
              const SizedBox(height: 16),
              VisitorDropdown<String>(
                label: 'Purpose (Optional)',
                hint: 'Select Purpose',
                icon: Icons.assignment_rounded,
                iconColor: const Color(0xFFF5A623),
                value: _purposeNote,
                items: _purposeOptions.map((p) => DropdownMenuItem(value: p, child: Text(p))).toList(),
                onChanged: (value) => setState(() => _purposeNote = value),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

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

/// "Pre-Approval Request" — the short form: photo, name, mobile and type.
/// It has no dates, so the visitor stays pre-approved until they turn up.
class PreApprovalRequestScreen extends ConsumerStatefulWidget {
  const PreApprovalRequestScreen({super.key});

  @override
  ConsumerState<PreApprovalRequestScreen> createState() => _PreApprovalRequestScreenState();
}

class _PreApprovalRequestScreenState extends ConsumerState<PreApprovalRequestScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _phoneController = TextEditingController();

  String? _type;
  Flat? _flat;
  String? _photoPath;
  bool _isSubmitting = false;

  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    super.dispose();
  }

  Future<void> _submit(Flat? flat) async {
    if (!_formKey.currentState!.validate() || flat == null) return;

    setState(() => _isSubmitting = true);
    try {
      await ref.read(visitorRepositoryProvider).invite(
            flatId: flat.id,
            visitorName: _nameController.text.trim(),
            visitorPhone: _phoneController.text.trim(),
            purpose: _type!,
            kind: 'pre_approval',
            photoPath: _photoPath,
          );
      ref.invalidate(preApprovedListProvider);
      ref.invalidate(visitorListProvider);
      if (!mounted) return;
      showVisitorSnack(context, 'Visitor pre-approved');
      context.pop();
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

    return VisitorScaffold(
      title: 'Pre-Approval Request',
      bottom: VisitorPrimaryButton(
        label: 'Save',
        loading: _isSubmitting,
        onPressed: flat == null ? null : () => _submit(flat),
      ),
      body: SingleChildScrollView(
        keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
        padding: const EdgeInsets.fromLTRB(20, 24, 20, 24),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              VisitorPhotoPicker(path: _photoPath, size: 124, onChanged: (path) => setState(() => _photoPath = path)),
              const SizedBox(height: 24),
              FlatPicker(flats: flats, value: flat, onChanged: (value) => setState(() => _flat = value)),
              VisitorTextField(
                label: 'Name',
                hint: 'Enter name',
                icon: Icons.person_rounded,
                controller: _nameController,
                required: true,
              ),
              const SizedBox(height: 18),
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
              const SizedBox(height: 18),
              VisitorDropdown<String>(
                label: 'Type',
                hint: 'Select Type',
                icon: Icons.category_rounded,
                iconColor: VisitorColors.success,
                required: true,
                value: _type,
                items: Visitor.purposes.map((p) => DropdownMenuItem(value: p, child: Text(purposeLabel(p)))).toList(),
                onChanged: (value) => setState(() => _type = value),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/api/api_exception.dart';
import '../../../core/models/flat.dart';
import '../data/daily_helper.dart';
import '../data/daily_helper_repository.dart';
import '../providers/visitor_providers.dart';
import '../widgets/flat_picker.dart';
import '../widgets/visitor_ui.dart';

/// Add a daily helper: photo, name, mobile and what they do.
class DailyHelperFormScreen extends ConsumerStatefulWidget {
  const DailyHelperFormScreen({super.key});

  @override
  ConsumerState<DailyHelperFormScreen> createState() => _DailyHelperFormScreenState();
}

class _DailyHelperFormScreenState extends ConsumerState<DailyHelperFormScreen> {
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
      await ref.read(dailyHelperRepositoryProvider).add(
            flatId: flat.id,
            name: _nameController.text.trim(),
            phone: _phoneController.text.trim(),
            helperType: _type!,
            photoPath: _photoPath,
          );
      ref.invalidate(dailyHelperListProvider);
      if (!mounted) return;
      showVisitorSnack(context, 'Helper added');
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
      title: 'Add Daily Helper',
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
                hint: 'Enter helper name',
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
                label: 'Work Type',
                hint: 'Select Type',
                icon: Icons.work_rounded,
                iconColor: const Color(0xFFF5A623),
                required: true,
                value: _type,
                items: DailyHelper.types.map((t) => DropdownMenuItem(value: t, child: Text(purposeLabel(t)))).toList(),
                onChanged: (value) => setState(() => _type = value),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

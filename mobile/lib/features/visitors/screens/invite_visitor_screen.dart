import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/api/api_exception.dart';
import '../../../core/models/flat.dart';
import '../../auth/providers/auth_provider.dart';
import '../data/visitor.dart';
import '../data/visitor_repository.dart';
import '../providers/visitor_providers.dart';

/// "Invite Visitor" (Visitors tab) and "Gate Pass" (Community tab) in the
/// mockup are the same create flow — see StoreVisitorInviteRequest on the
/// backend — so this one form serves both entry points.
class InviteVisitorScreen extends ConsumerStatefulWidget {
  const InviteVisitorScreen({super.key});

  @override
  ConsumerState<InviteVisitorScreen> createState() => _InviteVisitorScreenState();
}

class _InviteVisitorScreenState extends ConsumerState<InviteVisitorScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _phoneController = TextEditingController();
  final _vehicleController = TextEditingController();
  String _purpose = Visitor.purposes.first;
  DateTime? _expectedAt;
  Flat? _flat;
  bool _isSubmitting = false;

  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    _vehicleController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate() || _flat == null) return;

    setState(() => _isSubmitting = true);
    try {
      final visitor = await ref.read(visitorRepositoryProvider).invite(
            flatId: _flat!.id,
            visitorName: _nameController.text.trim(),
            visitorPhone: _phoneController.text.trim(),
            purpose: _purpose,
            vehicleNumber: _vehicleController.text.trim(),
            expectedAt: _expectedAt,
          );
      ref.invalidate(visitorListProvider(null));
      if (!mounted) return;
      context.pushReplacement('/visitors/gate-pass', extra: visitor);
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final flats = ref.watch(authControllerProvider).valueOrNull?.user.flats.map((r) => r.flat).toList() ?? [];
    _flat ??= flats.isNotEmpty ? flats.first : null;

    return Scaffold(
      appBar: AppBar(title: const Text('Invite Visitor')),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                if (flats.length > 1)
                  DropdownButtonFormField<Flat>(
                    initialValue: _flat,
                    decoration: const InputDecoration(labelText: 'Flat'),
                    items: flats.map((flat) => DropdownMenuItem(value: flat, child: Text(flat.displayLabel))).toList(),
                    onChanged: (value) => setState(() => _flat = value),
                  ),
                if (flats.length > 1) const SizedBox(height: 16),
                TextFormField(
                  controller: _nameController,
                  decoration: const InputDecoration(labelText: 'Visitor Name'),
                  validator: (value) => (value == null || value.trim().isEmpty) ? 'Enter the visitor\'s name' : null,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _phoneController,
                  keyboardType: TextInputType.phone,
                  decoration: const InputDecoration(labelText: 'Phone (optional)'),
                ),
                const SizedBox(height: 16),
                DropdownButtonFormField<String>(
                  initialValue: _purpose,
                  decoration: const InputDecoration(labelText: 'Purpose'),
                  items: Visitor.purposes.map((p) => DropdownMenuItem(value: p, child: Text(p.replaceAll('_', ' ')))).toList(),
                  onChanged: (value) => setState(() => _purpose = value!),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _vehicleController,
                  decoration: const InputDecoration(labelText: 'Vehicle Number (optional)'),
                ),
                const SizedBox(height: 16),
                ListTile(
                  contentPadding: EdgeInsets.zero,
                  title: Text(_expectedAt == null ? 'Expected arrival (optional)' : 'Expected: ${_expectedAt!.day}/${_expectedAt!.month} ${_expectedAt!.hour}:${_expectedAt!.minute.toString().padLeft(2, '0')}'),
                  trailing: const Icon(Icons.calendar_today_outlined),
                  onTap: () async {
                    final date = await showDatePicker(
                      context: context,
                      initialDate: DateTime.now(),
                      firstDate: DateTime.now(),
                      lastDate: DateTime.now().add(const Duration(days: 30)),
                    );
                    if (date == null || !context.mounted) return;
                    final time = await showTimePicker(context: context, initialTime: TimeOfDay.now());
                    if (time == null) return;
                    setState(() => _expectedAt = DateTime(date.year, date.month, date.day, time.hour, time.minute));
                  },
                ),
                const SizedBox(height: 24),
                ElevatedButton(
                  onPressed: (_isSubmitting || _flat == null) ? null : _submit,
                  child: _isSubmitting
                      ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                      : const Text('Generate Gate Pass'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/api/api_exception.dart';
import '../../../core/models/flat.dart';
import '../../auth/providers/auth_provider.dart';
import '../data/maintenance_request.dart';
import '../data/maintenance_request_repository.dart';
import '../providers/maintenance_request_providers.dart';

class MaintenanceRequestFormScreen extends ConsumerStatefulWidget {
  const MaintenanceRequestFormScreen({super.key});

  @override
  ConsumerState<MaintenanceRequestFormScreen> createState() => _MaintenanceRequestFormScreenState();
}

class _MaintenanceRequestFormScreenState extends ConsumerState<MaintenanceRequestFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _titleController = TextEditingController();
  final _descriptionController = TextEditingController();
  String _category = MaintenanceRequest.categories.first;
  String _priority = 'medium';
  Flat? _flat;
  bool _isSubmitting = false;

  @override
  void dispose() {
    _titleController.dispose();
    _descriptionController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate() || _flat == null) return;

    setState(() => _isSubmitting = true);
    try {
      await ref.read(maintenanceRequestRepositoryProvider).create(
            flatId: _flat!.id,
            category: _category,
            title: _titleController.text.trim(),
            description: _descriptionController.text.trim(),
            priority: _priority,
          );
      ref.invalidate(maintenanceRequestListProvider(null));
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Request logged successfully.')));
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
    final flats = ref.watch(authControllerProvider).valueOrNull?.user.flats.map((r) => r.flat).toList() ?? [];
    _flat ??= flats.isNotEmpty ? flats.first : null;

    return Scaffold(
      appBar: AppBar(title: const Text('New Maintenance Request')),
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
                    items: flats
                        .map((flat) => DropdownMenuItem(value: flat, child: Text(flat.displayLabel)))
                        .toList(),
                    onChanged: (value) => setState(() => _flat = value),
                  ),
                if (flats.length > 1) const SizedBox(height: 16),
                DropdownButtonFormField<String>(
                  initialValue: _category,
                  decoration: const InputDecoration(labelText: 'Category'),
                  items: MaintenanceRequest.categories
                      .map((c) => DropdownMenuItem(value: c, child: Text(c.replaceAll('_', ' '))))
                      .toList(),
                  onChanged: (value) => setState(() => _category = value!),
                ),
                const SizedBox(height: 16),
                DropdownButtonFormField<String>(
                  initialValue: _priority,
                  decoration: const InputDecoration(labelText: 'Priority'),
                  items: MaintenanceRequest.priorities
                      .map((p) => DropdownMenuItem(value: p, child: Text(p)))
                      .toList(),
                  onChanged: (value) => setState(() => _priority = value!),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _titleController,
                  decoration: const InputDecoration(labelText: 'Title'),
                  validator: (value) => (value == null || value.trim().isEmpty) ? 'Enter a short title' : null,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _descriptionController,
                  decoration: const InputDecoration(labelText: 'Description'),
                  maxLines: 4,
                  validator: (value) => (value == null || value.trim().isEmpty) ? 'Describe the issue' : null,
                ),
                const SizedBox(height: 24),
                ElevatedButton(
                  onPressed: (_isSubmitting || _flat == null) ? null : _submit,
                  child: _isSubmitting
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                        )
                      : const Text('Submit Request'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

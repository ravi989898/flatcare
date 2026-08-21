import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_exception.dart';
import '../../../core/widgets/async_view.dart';
import '../data/family_member.dart';
import '../data/profile_repository.dart';
import '../providers/profile_providers.dart';

class FamilyMembersScreen extends ConsumerWidget {
  const FamilyMembersScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final members = ref.watch(familyMemberListProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Family Members')),
      floatingActionButton: FloatingActionButton(
        onPressed: () => _showAddDialog(context, ref),
        child: const Icon(Icons.add),
      ),
      body: AsyncView<List<FamilyMember>>(
        value: members,
        onRetry: () => ref.invalidate(familyMemberListProvider),
        builder: (context, items) {
          if (items.isEmpty) {
            return const EmptyState(message: 'No family members added yet.', icon: Icons.family_restroom_outlined);
          }

          return ListView.separated(
            padding: const EdgeInsets.all(16),
            itemCount: items.length,
            separatorBuilder: (_, __) => const SizedBox(height: 8),
            itemBuilder: (context, index) {
              final member = items[index];

              return Card(
                child: ListTile(
                  title: Text(member.name),
                  subtitle: Text([member.relation, member.phone].whereType<String>().join(' · ')),
                  trailing: IconButton(
                    icon: const Icon(Icons.delete_outline),
                    onPressed: () async {
                      try {
                        await ref.read(profileRepositoryProvider).deleteFamilyMember(member.id);
                        ref.invalidate(familyMemberListProvider);
                      } on ApiException catch (e) {
                        if (context.mounted) {
                          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
                        }
                      }
                    },
                  ),
                ),
              );
            },
          );
        },
      ),
    );
  }

  Future<void> _showAddDialog(BuildContext context, WidgetRef ref) async {
    final nameController = TextEditingController();
    final relationController = TextEditingController();
    final phoneController = TextEditingController();
    final formKey = GlobalKey<FormState>();

    await showDialog(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Add Family Member'),
        content: Form(
          key: formKey,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextFormField(
                controller: nameController,
                decoration: const InputDecoration(labelText: 'Name'),
                validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
              ),
              TextFormField(
                controller: relationController,
                decoration: const InputDecoration(labelText: 'Relation'),
                validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
              ),
              TextFormField(
                controller: phoneController,
                decoration: const InputDecoration(labelText: 'Phone (optional)'),
              ),
            ],
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.of(dialogContext).pop(), child: const Text('Cancel')),
          TextButton(
            onPressed: () async {
              if (!formKey.currentState!.validate()) return;
              try {
                await ref.read(profileRepositoryProvider).addFamilyMember({
                  'name': nameController.text.trim(),
                  'relation': relationController.text.trim(),
                  if (phoneController.text.trim().isNotEmpty) 'phone': phoneController.text.trim(),
                });
                ref.invalidate(familyMemberListProvider);
                if (dialogContext.mounted) Navigator.of(dialogContext).pop();
              } on ApiException catch (e) {
                if (dialogContext.mounted) {
                  ScaffoldMessenger.of(dialogContext).showSnackBar(SnackBar(content: Text(e.message)));
                }
              }
            },
            child: const Text('Add'),
          ),
        ],
      ),
    );
  }
}

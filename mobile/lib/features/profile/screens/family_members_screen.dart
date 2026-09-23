import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_exception.dart';
import '../../../core/widgets/fc/fc.dart';
import '../data/family_member.dart';
import '../data/profile_repository.dart';
import '../providers/profile_providers.dart';

/// A relation choice plus the accent/icon it's shown with. Accents are
/// grouped by kind (parents, spouse, children, siblings, in-laws) so the
/// list stays calm instead of giving every relation its own color.
class _Relation {
  const _Relation(this.label, this.icon, this.color);

  final String label;
  final IconData icon;
  final Color color;
}

const _other = 'Other';

const _relations = [
  _Relation('Father', Icons.man_rounded, AppColors.accentIndigo),
  _Relation('Mother', Icons.woman_rounded, AppColors.accentIndigo),
  _Relation('Husband', Icons.man_rounded, AppColors.accentRose),
  _Relation('Wife', Icons.woman_rounded, AppColors.accentRose),
  _Relation('Son', Icons.boy_rounded, AppColors.accentTeal),
  _Relation('Daughter', Icons.girl_rounded, AppColors.accentTeal),
  _Relation('Brother', Icons.man_rounded, AppColors.accentSky),
  _Relation('Sister', Icons.woman_rounded, AppColors.accentSky),
  _Relation('Grandfather', Icons.elderly_rounded, AppColors.accentViolet),
  _Relation('Grandmother', Icons.elderly_woman_rounded, AppColors.accentViolet),
  _Relation('Father-in-law', Icons.man_rounded, AppColors.accentAmber),
  _Relation('Mother-in-law', Icons.woman_rounded, AppColors.accentAmber),
  _Relation('Brother-in-law', Icons.man_rounded, AppColors.accentAmber),
  _Relation('Sister-in-law', Icons.woman_rounded, AppColors.accentAmber),
  _Relation(_other, Icons.person_rounded, AppColors.accentSlate),
];

/// Older records were saved against the previous enum (spouse, child, ...);
/// show those with a sensible accent too.
const _legacyColors = {
  'spouse': AppColors.accentRose,
  'child': AppColors.accentTeal,
  'parent': AppColors.accentIndigo,
  'sibling': AppColors.accentSky,
};

_Relation? _relationFor(String value) =>
    _relations.where((r) => r.label.toLowerCase() == value.trim().toLowerCase()).firstOrNull;

Color _colorFor(String relation) =>
    _relationFor(relation)?.color ?? _legacyColors[relation.toLowerCase()] ?? AppColors.accentSlate;

String _displayRelation(String relation) {
  final value = relation.trim();
  if (value.isEmpty) return _other;
  return value[0].toUpperCase() + value.substring(1);
}

final _phonePattern = RegExp(r'^\+?[0-9 ]{7,15}$');

class FamilyMembersScreen extends ConsumerWidget {
  const FamilyMembersScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final members = ref.watch(familyMemberListProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Family Members')),
      floatingActionButton: FcFab(
        label: 'Add Family Member',
        icon: Icons.person_add_alt_1_rounded,
        onPressed: () => _openForm(context, ref),
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(familyMemberListProvider.future),
        child: AsyncView<List<FamilyMember>>(
          value: members,
          skeleton: true,
          onRetry: () => ref.invalidate(familyMemberListProvider),
          builder: (context, items) {
            if (items.isEmpty) {
              return EmptyState(
                icon: Icons.family_restroom_rounded,
                title: 'No family members yet',
                message: 'Add the people who live with you so the gate and your society know your household.',
                action: FilledButton.icon(
                  onPressed: () => _openForm(context, ref),
                  icon: const Icon(Icons.person_add_alt_1_rounded),
                  label: const Text('Add Family Member'),
                ),
              );
            }

            return ListView.separated(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 100),
              itemCount: items.length + 1,
              separatorBuilder: (_, __) => const SizedBox(height: 12),
              itemBuilder: (context, index) {
                if (index == 0) return _HouseholdBanner(count: items.length);
                final member = items[index - 1];
                final color = _colorFor(member.relation);

                return FcListCard(
                  leading: FcInitialsAvatar(name: member.name, color: color),
                  title: member.name,
                  meta: [
                    if (member.phone != null && member.phone!.isNotEmpty) FcMeta(Icons.phone_outlined, member.phone!),
                  ],
                  badges: [
                    FcBadge(
                      label: _displayRelation(member.relation),
                      color: color,
                      icon: _relationFor(member.relation)?.icon ?? Icons.person_rounded,
                    ),
                  ],
                  onTap: () => _openForm(context, ref, member: member),
                  trailing: FcCardMenu(
                    items: [
                      FcCardMenuItem(
                        label: 'Edit',
                        icon: Icons.edit_outlined,
                        onSelected: () => _openForm(context, ref, member: member),
                      ),
                      FcCardMenuItem(
                        label: 'Remove',
                        icon: Icons.delete_outline_rounded,
                        danger: true,
                        onSelected: () => _confirmDelete(context, ref, member),
                      ),
                    ],
                  ),
                );
              },
            );
          },
        ),
      ),
    );
  }

  Future<void> _confirmDelete(BuildContext context, WidgetRef ref, FamilyMember member) async {
    final confirmed = await showFcConfirmDialog(
      context,
      title: 'Remove family member?',
      message: 'Are you sure you want to remove ${member.name} from your family members?',
      confirmLabel: 'Remove',
      danger: true,
    );
    if (!confirmed) return;

    try {
      await ref.read(profileRepositoryProvider).deleteFamilyMember(member.id);
      ref.invalidate(familyMemberListProvider);
      if (context.mounted) showFcSnack(context, '${member.name} removed.');
    } on ApiException catch (e) {
      if (context.mounted) showFcSnack(context, e.message, error: true);
    }
  }

  Future<void> _openForm(BuildContext context, WidgetRef ref, {FamilyMember? member}) async {
    final isEdit = member != null;
    final nameController = TextEditingController(text: member?.name);
    final phoneController = TextEditingController(text: member?.phone);
    final otherController = TextEditingController();

    String? relation;
    if (member != null) {
      final known = _relationFor(member.relation);
      if (known != null) {
        relation = known.label;
      } else {
        relation = _other;
        otherController.text = _displayRelation(member.relation);
      }
    }

    final saved = await FcFormSheet.show(
      context,
      title: isEdit ? 'Edit Family Member' : 'Add Family Member',
      subtitle: isEdit ? 'Update ${member.name}\'s details' : 'Someone who lives in your flat',
      icon: isEdit ? Icons.edit_rounded : Icons.person_add_alt_1_rounded,
      submitLabel: isEdit ? 'Save Changes' : 'Add Member',
      fieldsBuilder: (context, setState, errors) => [
        TextFormField(
          controller: nameController,
          textCapitalization: TextCapitalization.words,
          textInputAction: TextInputAction.next,
          decoration: InputDecoration(
            labelText: 'Full name *',
            prefixIcon: const Icon(Icons.person_outline_rounded),
            errorText: errors['name'],
          ),
          validator: (v) {
            final value = v?.trim() ?? '';
            if (value.isEmpty) return 'Please enter the name';
            if (value.length > 255) return 'Name is too long';
            return null;
          },
        ),
        FcSelectField(
          label: 'Relation',
          initialValue: relation,
          prefixIcon: Icons.diversity_1_outlined,
          errorText: relation == _other ? null : errors['relation'],
          options: [for (final r in _relations) FcOption(r.label, r.label, icon: r.icon, color: r.color)],
          onChanged: (value) => setState(() => relation = value),
        ),
        if (relation == _other)
          TextFormField(
            controller: otherController,
            textCapitalization: TextCapitalization.words,
            decoration: InputDecoration(
              labelText: 'Enter relation *',
              hintText: 'e.g. Cousin, Nephew',
              prefixIcon: const Icon(Icons.edit_outlined),
              errorText: errors['relation'],
            ),
            validator: (v) {
              final value = v?.trim() ?? '';
              if (value.isEmpty) return 'Please enter the relation';
              if (value.length > 100) return 'Relation is too long';
              return null;
            },
          ),
        TextFormField(
          controller: phoneController,
          keyboardType: TextInputType.phone,
          decoration: InputDecoration(
            labelText: 'Phone number (optional)',
            prefixIcon: const Icon(Icons.phone_outlined),
            errorText: errors['phone'],
          ),
          validator: (v) {
            final value = v?.trim() ?? '';
            if (value.isEmpty) return null;
            return _phonePattern.hasMatch(value) ? null : 'Enter a valid phone number';
          },
        ),
      ],
      onSubmit: () async {
        final phone = phoneController.text.trim();
        final payload = <String, dynamic>{
          'name': nameController.text.trim(),
          'relation': relation == _other ? otherController.text.trim() : relation,
          // Sent as null (not omitted) on edit so clearing the field clears it.
          'phone': phone.isEmpty ? null : phone,
        };
        final repository = ref.read(profileRepositoryProvider);
        if (isEdit) {
          await repository.updateFamilyMember(member.id, payload);
        } else {
          await repository.addFamilyMember(payload);
        }
      },
    );

    if (saved) {
      ref.invalidate(familyMemberListProvider);
      if (context.mounted) {
        showFcSnack(context, isEdit ? 'Family member updated.' : '${nameController.text.trim()} added to your family.');
      }
    }
  }
}

class _HouseholdBanner extends StatelessWidget {
  const _HouseholdBanner({required this.count});

  final int count;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(gradient: AppColors.primaryGradient, borderRadius: BorderRadius.circular(18)),
      child: Row(
        children: [
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.2), borderRadius: BorderRadius.circular(14)),
            child: const Icon(Icons.family_restroom_rounded, color: Colors.white, size: 26),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '$count ${count == 1 ? 'member' : 'members'}',
                  style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.w800),
                ),
                const Text(
                  'in your household',
                  style: TextStyle(color: Colors.white, fontSize: 13.5, fontWeight: FontWeight.w500),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/widgets/async_view.dart';
import '../data/emergency_contact.dart';
import '../providers/emergency_contact_providers.dart';

const _typeIcons = {
  'security': Icons.shield_outlined,
  'ambulance': Icons.local_hospital_outlined,
  'fire': Icons.local_fire_department_outlined,
  'police': Icons.local_police_outlined,
};

class EmergencyContactsScreen extends ConsumerWidget {
  const EmergencyContactsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final contacts = ref.watch(emergencyContactListProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Emergency Contacts')),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(emergencyContactListProvider.future),
        child: AsyncView<List<EmergencyContact>>(
          value: contacts,
          onRetry: () => ref.invalidate(emergencyContactListProvider),
          builder: (context, items) {
            if (items.isEmpty) {
              return const EmptyState(message: 'No emergency contacts added yet.', icon: Icons.phone_disabled_outlined);
            }

            return ListView.separated(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              itemCount: items.length,
              separatorBuilder: (_, __) => const SizedBox(height: 8),
              itemBuilder: (context, index) {
                final contact = items[index];

                return Card(
                  child: ListTile(
                    leading: CircleAvatar(
                      backgroundColor: Colors.red.withValues(alpha: 0.1),
                      child: Icon(_typeIcons[contact.type] ?? Icons.phone_outlined, color: Colors.red),
                    ),
                    title: Text(contact.label, style: const TextStyle(fontWeight: FontWeight.w600)),
                    subtitle: Text(contact.availability ?? contact.phone),
                    trailing: IconButton(
                      icon: const Icon(Icons.call, color: Colors.green),
                      onPressed: () => launchUrl(Uri.parse('tel:${contact.phone}')),
                    ),
                    onTap: () => launchUrl(Uri.parse('tel:${contact.phone}')),
                  ),
                );
              },
            );
          },
        ),
      ),
    );
  }
}

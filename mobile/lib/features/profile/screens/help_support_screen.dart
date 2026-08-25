import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/widgets/async_view.dart';
import '../../emergency_contacts/data/emergency_contact.dart';
import '../../emergency_contacts/providers/emergency_contact_providers.dart';

/// Reuses the same society-managed Emergency Contacts list as Help &
/// Support's "who do I call" content, per the implementation plan's
/// Profile-menu scope decision — no separate support-ticket backend.
class HelpSupportScreen extends ConsumerWidget {
  const HelpSupportScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final contacts = ref.watch(emergencyContactListProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Help & Support')),
      body: AsyncView<List<EmergencyContact>>(
        value: contacts,
        onRetry: () => ref.invalidate(emergencyContactListProvider),
        builder: (context, items) {
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              const Text('Need help? Reach out to your society office or the contacts below.', style: TextStyle(color: Colors.black54)),
              const SizedBox(height: 16),
              for (final contact in items)
                Card(
                  child: ListTile(
                    leading: const Icon(Icons.support_agent_outlined),
                    title: Text(contact.label),
                    subtitle: Text(contact.phone),
                    trailing: IconButton(
                      icon: const Icon(Icons.call, color: Colors.green),
                      onPressed: () => launchUrl(Uri.parse('tel:${contact.phone}')),
                    ),
                  ),
                ),
            ],
          );
        },
      ),
    );
  }
}

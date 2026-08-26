import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/widgets/async_view.dart';
import '../data/service_provider.dart';
import '../providers/service_provider_providers.dart';

/// Society-vetted plumbers/electricians/etc. residents can browse and call
/// — same list/call pattern as Emergency Contacts, different data source.
class ServiceProviderListScreen extends ConsumerWidget {
  const ServiceProviderListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final providers = ref.watch(serviceProviderListProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Service Providers')),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(serviceProviderListProvider.future),
        child: AsyncView<List<ServiceProvider>>(
          value: providers,
          onRetry: () => ref.invalidate(serviceProviderListProvider),
          builder: (context, items) {
            if (items.isEmpty) {
              return const EmptyState(message: 'No service providers added yet.', icon: Icons.handyman_outlined);
            }

            return ListView.separated(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              itemCount: items.length,
              separatorBuilder: (_, __) => const SizedBox(height: 8),
              itemBuilder: (context, index) {
                final provider = items[index];

                return Card(
                  child: ListTile(
                    leading: const CircleAvatar(child: Icon(Icons.handyman_outlined)),
                    title: Text(provider.name, style: const TextStyle(fontWeight: FontWeight.w600)),
                    subtitle: Text([provider.serviceType, provider.notes].whereType<String>().join(' · ')),
                    trailing: IconButton(
                      icon: const Icon(Icons.call, color: Colors.green),
                      onPressed: () => launchUrl(Uri.parse('tel:${provider.phone}')),
                    ),
                    onTap: () => launchUrl(Uri.parse('tel:${provider.phone}')),
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

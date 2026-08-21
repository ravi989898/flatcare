import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../auth/providers/auth_provider.dart';
import '../../../core/widgets/async_view.dart';

class ProfileScreen extends ConsumerWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authControllerProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Profile')),
      body: AsyncView(
        value: auth,
        onRetry: () => ref.invalidate(authControllerProvider),
        builder: (context, state) {
          if (state == null) return const SizedBox.shrink();
          final user = state.user;

          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              Center(
                child: CircleAvatar(
                  radius: 40,
                  child: Text(
                    user.name.isNotEmpty ? user.name[0].toUpperCase() : '?',
                    style: const TextStyle(fontSize: 28),
                  ),
                ),
              ),
              const SizedBox(height: 12),
              Text(user.name, textAlign: TextAlign.center, style: Theme.of(context).textTheme.titleLarge),
              Text(
                state.society.name,
                textAlign: TextAlign.center,
                style: Theme.of(context).textTheme.bodyMedium,
              ),
              const SizedBox(height: 24),
              Card(
                child: Column(
                  children: [
                    ListTile(leading: const Icon(Icons.email_outlined), title: Text(user.email)),
                    if (user.phone != null)
                      ListTile(leading: const Icon(Icons.phone_outlined), title: Text(user.phone!)),
                    if (user.primaryResidency != null)
                      ListTile(
                        leading: const Icon(Icons.apartment_outlined),
                        title: Text(user.primaryResidency!.flat.displayLabel),
                        subtitle: Text(user.primaryResidency!.residentType),
                      ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              ListTile(
                leading: const Icon(Icons.edit_outlined),
                title: const Text('Edit profile'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => context.push('/profile/edit'),
              ),
              ListTile(
                leading: const Icon(Icons.family_restroom_outlined),
                title: const Text('Family members'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => context.push('/profile/family-members'),
              ),
              ListTile(
                leading: const Icon(Icons.directions_car_outlined),
                title: const Text('Vehicles'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => context.push('/profile/vehicles'),
              ),
              const Divider(height: 32),
              ListTile(
                leading: Icon(Icons.logout, color: Theme.of(context).colorScheme.error),
                title: Text('Sign out', style: TextStyle(color: Theme.of(context).colorScheme.error)),
                onTap: () async {
                  final confirmed = await showDialog<bool>(
                    context: context,
                    builder: (context) => AlertDialog(
                      title: const Text('Sign out?'),
                      actions: [
                        TextButton(onPressed: () => context.pop(false), child: const Text('Cancel')),
                        TextButton(onPressed: () => context.pop(true), child: const Text('Sign out')),
                      ],
                    ),
                  );
                  if (confirmed == true) {
                    await ref.read(authControllerProvider.notifier).logout();
                  }
                },
              ),
            ],
          );
        },
      ),
    );
  }
}

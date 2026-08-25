import 'package:flutter/material.dart';
import 'package:flutter_cache_manager/flutter_cache_manager.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:share_plus/share_plus.dart';

import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/photo_avatar.dart';
import '../../auth/providers/auth_provider.dart';

/// Redesigned to match the mockup: a gradient header card (avatar, name,
/// unit, phone) followed by a flat menu list (Edit Profile, Change
/// Password, Notification Settings, Language, Help & Support, About Us),
/// rather than the earlier Card-with-ListTiles contact-info layout.
class ProfileScreen extends ConsumerWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authControllerProvider);

    return Scaffold(
      backgroundColor: const Color(0xFFF2F3F7),
      appBar: AppBar(title: const Text('Profile')),
      body: AsyncView(
        value: auth,
        onRetry: () => ref.invalidate(authControllerProvider),
        builder: (context, state) {
          if (state == null) return const SizedBox.shrink();
          final user = state.user;
          final unit = user.primaryResidency?.flat.displayLabel;

          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              _ProfileHeaderCard(
                name: user.name,
                unit: unit,
                society: state.society.name,
                phone: user.phone,
                photoUrl: user.profilePhotoUrl,
              ),
              const SizedBox(height: 20),
              _ProfileMenuTile(icon: Icons.edit_outlined, label: 'Edit Profile', onTap: () => context.push('/profile/edit')),
              _ProfileMenuTile(
                icon: Icons.lock_outline,
                label: 'Change Password',
                onTap: () => context.push('/profile/change-password'),
              ),
              _ProfileMenuTile(
                icon: Icons.notifications_none,
                label: 'Notification Settings',
                onTap: () => context.push('/profile/notification-settings'),
              ),
              _ProfileMenuTile(icon: Icons.language_outlined, label: 'Language', onTap: () => context.push('/profile/language')),
              _ProfileMenuTile(
                icon: Icons.help_outline,
                label: 'Help & Support',
                onTap: () => context.push('/profile/help-support'),
              ),
              _ProfileMenuTile(icon: Icons.info_outline, label: 'About Us', onTap: () => context.push('/profile/about')),
              _ProfileMenuTile(icon: Icons.dark_mode_outlined, label: 'Theme', onTap: () => context.push('/profile/theme')),
              _ProfileMenuTile(
                icon: Icons.privacy_tip_outlined,
                label: 'Privacy Policy',
                onTap: () => context.push('/profile/privacy-policy'),
              ),
              _ProfileMenuTile(icon: Icons.gavel_outlined, label: 'Terms & Conditions', onTap: () => context.push('/profile/terms')),
              _ProfileMenuTile(
                icon: Icons.star_outline,
                label: 'Rate Us',
                onTap: () => showDialog<void>(
                  context: context,
                  builder: (context) => AlertDialog(
                    title: const Text('Rate FlatCare'),
                    content: const Text('Thanks for using FlatCare! The app isn\'t published to an app store yet — check back once it is.'),
                    actions: [TextButton(onPressed: () => Navigator.of(context).pop(), child: const Text('OK'))],
                  ),
                ),
              ),
              _ProfileMenuTile(
                icon: Icons.share_outlined,
                label: 'Share App',
                onTap: () => Share.share('Manage your society life with FlatCare — ask your society admin for an account.'),
              ),
              _ProfileMenuTile(
                icon: Icons.cleaning_services_outlined,
                label: 'Clear Cache',
                onTap: () async {
                  await DefaultCacheManager().emptyCache();
                  if (context.mounted) {
                    ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Cache cleared.')));
                  }
                },
              ),
              const Divider(height: 32),
              _ProfileMenuTile(
                icon: Icons.family_restroom_outlined,
                label: 'Family Members',
                onTap: () => context.push('/profile/family-members'),
              ),
              _ProfileMenuTile(
                icon: Icons.directions_car_outlined,
                label: 'Vehicles',
                onTap: () => context.push('/profile/vehicles'),
              ),
              const Divider(height: 32),
              _ProfileMenuTile(
                icon: Icons.logout,
                label: 'Sign Out',
                color: Theme.of(context).colorScheme.error,
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

class _ProfileHeaderCard extends StatelessWidget {
  const _ProfileHeaderCard({required this.name, this.unit, required this.society, this.phone, this.photoUrl});

  final String name;
  final String? unit;
  final String society;
  final String? phone;
  final String? photoUrl;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 10, offset: const Offset(0, 3))],
      ),
      child: Column(
        children: [
          PhotoAvatar(url: photoUrl, name: name, radius: 36),
          const SizedBox(height: 12),
          Text(name, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
          const SizedBox(height: 2),
          Text([if (unit != null) unit!, society].join(', '), style: const TextStyle(color: Colors.black54)),
          if (phone != null) ...[
            const SizedBox(height: 2),
            Text(phone!, style: const TextStyle(color: Colors.black54)),
          ],
        ],
      ),
    );
  }
}

class _ProfileMenuTile extends StatelessWidget {
  const _ProfileMenuTile({required this.icon, required this.label, required this.onTap, this.color});

  final IconData icon;
  final String label;
  final VoidCallback onTap;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: Icon(icon, color: color),
        title: Text(label, style: TextStyle(fontWeight: FontWeight.w600, color: color)),
        trailing: const Icon(Icons.chevron_right),
        onTap: onTap,
      ),
    );
  }
}

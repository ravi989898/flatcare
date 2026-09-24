import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/widgets/fc/fc_dialogs.dart';
import '../../../core/widgets/async_view.dart';
import '../../../core/widgets/photo_avatar.dart';
import '../../auth/providers/auth_provider.dart';

/// Header card (avatar, name, unit, phone) followed by the account-only
/// actions. General app items (settings, help, legal, share, cache) live in
/// the home drawer instead.
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
              _ProfileMenuTile(icon: Icons.edit_outlined, accent: AppColors.accentSky, label: 'Edit Profile', onTap: () => context.push('/profile/edit')),
              _ProfileMenuTile(
                icon: Icons.family_restroom_outlined,
                accent: AppColors.accentRose,
                label: 'Family Members',
                onTap: () => context.push('/profile/family-members'),
              ),
              _ProfileMenuTile(
                icon: Icons.directions_car_outlined,
                accent: AppColors.accentIndigo,
                label: 'Vehicles',
                onTap: () => context.push('/profile/vehicles'),
              ),
              const Divider(height: 32),
              _ProfileMenuTile(
                icon: Icons.logout,
                label: 'Sign Out',
                color: Theme.of(context).colorScheme.error,
                onTap: () async {
                  final confirmed = await showFcConfirmDialog(
                  context,
                  title: 'Sign out?',
                  message: 'You will stop receiving visitor alerts on this phone until you sign in again.',
                  confirmLabel: 'Sign out',
                  icon: Icons.logout_rounded,
                  danger: true,
                );
                if (confirmed) {
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
      clipBehavior: Clip.antiAlias,
      decoration: BoxDecoration(
        gradient: AppColors.primaryGradient,
        borderRadius: BorderRadius.circular(22),
        boxShadow: [BoxShadow(color: AppColors.primary.withValues(alpha: 0.30), blurRadius: 16, offset: const Offset(0, 6))],
      ),
      child: Stack(
        children: [
          // Soft decorative circles behind the content.
          Positioned(top: -30, right: -20, child: _Bubble(size: 110, alpha: 0.12)),
          Positioned(bottom: -40, left: -25, child: _Bubble(size: 120, alpha: 0.10)),
          Padding(
            padding: const EdgeInsets.all(22),
            child: Column(
              children: [
                Container(
                  padding: const EdgeInsets.all(3),
                  decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle),
                  child: PhotoAvatar(url: photoUrl, name: name, radius: 36),
                ),
                const SizedBox(height: 12),
                Text(name, style: const TextStyle(fontSize: 19, fontWeight: FontWeight.w800, color: Colors.white)),
                const SizedBox(height: 8),
                Wrap(
                  alignment: WrapAlignment.center,
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    _InfoChip(icon: Icons.apartment_rounded, text: [?unit, society].join(', ')),
                    if (phone != null) _InfoChip(icon: Icons.phone_rounded, text: phone!),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _Bubble extends StatelessWidget {
  const _Bubble({required this.size, required this.alpha});

  final double size;
  final double alpha;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(color: Colors.white.withValues(alpha: alpha), shape: BoxShape.circle),
    );
  }
}

class _InfoChip extends StatelessWidget {
  const _InfoChip({required this.icon, required this.text});

  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.2), borderRadius: BorderRadius.circular(999)),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: Colors.white),
          const SizedBox(width: 5),
          Flexible(child: Text(text, style: const TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.w600))),
        ],
      ),
    );
  }
}

class _ProfileMenuTile extends StatelessWidget {
  const _ProfileMenuTile({
    required this.icon,
    required this.label,
    required this.onTap,
    this.accent = AppColors.primary,
    this.color,
  });

  final IconData icon;
  final String label;
  final VoidCallback onTap;

  /// Icon color — each tile gets its own.
  final Color accent;

  /// Overrides both icon and label color (used for Sign Out).
  final Color? color;

  @override
  Widget build(BuildContext context) {
    final tint = color ?? accent;
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Material(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        child: InkWell(
          borderRadius: BorderRadius.circular(16),
          onTap: onTap,
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
            child: Row(
              children: [
                Container(
                  width: 42,
                  height: 42,
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                      colors: [tint.withValues(alpha: 0.75), tint],
                    ),
                    borderRadius: BorderRadius.circular(12),
                    boxShadow: [BoxShadow(color: tint.withValues(alpha: 0.30), blurRadius: 8, offset: const Offset(0, 3))],
                  ),
                  child: Icon(icon, color: Colors.white, size: 22),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Text(
                    label,
                    style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700, color: color ?? AppColors.textPrimary),
                  ),
                ),
                Icon(Icons.chevron_right, color: tint.withValues(alpha: 0.6)),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

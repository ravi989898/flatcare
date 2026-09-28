import 'package:flutter/material.dart';
import 'package:flutter_cache_manager/flutter_cache_manager.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:package_info_plus/package_info_plus.dart';
import 'package:share_plus/share_plus.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/widgets/fc/fc_dialogs.dart';
import '../../../core/widgets/photo_avatar.dart';
import '../../../core/theme/app_theme.dart';
import '../../auth/providers/auth_provider.dart';
import '../../notifications/providers/notification_providers.dart';

/// One tile in a menu group. `route` is null for features the backend
/// doesn't expose yet (see routes/api.php) — those still render, matching
/// the reference app's layout, but tapping them explains they're on the way
/// instead of pretending to work.
///
/// `emoji` (rather than a Material `IconData`) is what gives these tiles the
/// same colorful, illustrated "sticker" look as the reference screenshots —
/// emoji glyphs render as full-color art on every platform, so this needs no
/// bundled icon assets to look right.
class _MenuItem {
  const _MenuItem(this.label, this.emoji, {this.route});

  final String label;
  final String emoji;
  final String? route;
}

class _MenuGroup {
  const _MenuGroup(this.title, this.items);

  final String title;
  final List<_MenuItem> items;
}

const _groups = [
  _MenuGroup('Quick Access', [
    _MenuItem('My Bills', '🧾', route: '/bills'),
    _MenuItem('Complaints', '⚠️', route: '/complaints'),
  ]),
  _MenuGroup('Directory', [
    _MenuItem('Members', '👥', route: '/directory'),
    _MenuItem('Committee Members', '🧑‍💼', route: '/committee-members'),
    _MenuItem('Family Members', '👪', route: '/profile/family-members'),
    _MenuItem('Vehicles', '🚗', route: '/profile/vehicles'),
    _MenuItem('Important Contacts', '🚨', route: '/emergency-contacts'),
    _MenuItem('Service Providers', '🛠️', route: '/service-providers'),
  ]),
  _MenuGroup('Interaction', [
    _MenuItem('Meetings', '💬', route: '/events'),
    _MenuItem('Announcement', '📣', route: '/announcements'),
    _MenuItem('Event', '📅', route: '/events'),
    _MenuItem('Voting', '🗳️', route: '/polls'),
    _MenuItem('Amenities', '📋'),
    _MenuItem('Proposal', '📝'),
    _MenuItem('Suggestions', '💡'),
    _MenuItem('Tasks', '🗒️'),
    _MenuItem('Notifications', '🔔', route: '/notifications'),
  ]),
  _MenuGroup('Visitor', [
    _MenuItem('My Visitors', '🚪', route: '/visitors'),
    _MenuItem('My Daily Helpers', '🧹', route: '/visitors/helpers'),
    _MenuItem('Gate Keeper', '👮', route: '/visitors/gatekeeper'),
    _MenuItem('Gate Pass', '🎫', route: '/visitors/passes'),
    _MenuItem('Pre-Approve', '✅', route: '/visitors/pre-approved'),
    _MenuItem('Settings', '⚙️', route: '/visitors/settings'),
  ]),
  _MenuGroup('My Building', [
    _MenuItem('Documents', '📄', route: '/documents'),
    _MenuItem('Statistics', '📊'),
  ]),
];

class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authControllerProvider);
    final societyName = auth.valueOrNull?.society.name ?? 'FlatCare';
    final unit = auth.valueOrNull?.user.primaryResidency?.flat.displayLabel;

    return Scaffold(
      backgroundColor: const Color(0xFFF2F3F7),
      drawer: _HomeDrawer(societyName: societyName, unit: unit),
      body: SafeArea(
        bottom: false,
        child: Column(
          children: [
            _HomeHeader(societyName: societyName),
            Expanded(
              child: ListView(
                padding: EdgeInsets.fromLTRB(16, 16, 16, 16 + MediaQuery.paddingOf(context).bottom),
                children: [
                  for (final group in _groups) ...[
                    _GroupCard(group: group),
                    const SizedBox(height: 16),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _HomeHeader extends ConsumerWidget {
  const _HomeHeader({required this.societyName});

  final String societyName;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final unread = ref.watch(unreadNotificationCountProvider).valueOrNull ?? 0;

    return Container(
      decoration: const BoxDecoration(gradient: AppTheme.brandGradient),
      padding: const EdgeInsets.fromLTRB(4, 8, 12, 16),
      child: Row(
        children: [
          Builder(
            builder: (context) => IconButton(
              icon: const Icon(Icons.menu, color: Colors.white),
              onPressed: () => Scaffold.of(context).openDrawer(),
            ),
          ),
          Expanded(
            child: InkWell(
              borderRadius: BorderRadius.circular(24),
              onTap: () => context.push('/my-properties'),
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                decoration: BoxDecoration(
                  border: Border.all(color: Colors.white70),
                  borderRadius: BorderRadius.circular(24),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Flexible(
                      child: Text(
                        societyName,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w600),
                      ),
                    ),
                    const SizedBox(width: 6),
                    const Icon(Icons.keyboard_arrow_down, color: Colors.white, size: 20),
                  ],
                ),
              ),
            ),
          ),
          IconButton(
            tooltip: 'Notifications',
            icon: Badge(
              isLabelVisible: unread > 0,
              label: Text(unread > 99 ? '99+' : '$unread'),
              backgroundColor: AppColors.danger,
              child: const Icon(Icons.notifications_none_rounded, color: Colors.white),
            ),
            onPressed: () async {
              await context.push('/notifications');
              ref.invalidate(unreadNotificationCountProvider);
            },
          ),
        ],
      ),
    );
  }
}

class _GroupCard extends StatelessWidget {
  const _GroupCard({required this.group});

  final _MenuGroup group;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 8, offset: const Offset(0, 2))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(group.title, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
          const SizedBox(height: 12),
          GridView.count(
            crossAxisCount: 4,
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            mainAxisSpacing: 8,
            crossAxisSpacing: 4,
            childAspectRatio: 0.78,
            children: [for (final item in group.items) _MenuTile(item: item)],
          ),
        ],
      ),
    );
  }
}

class _MenuTile extends StatelessWidget {
  const _MenuTile({required this.item});

  final _MenuItem item;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(12),
      onTap: () {
        if (item.route != null) {
          context.push(item.route!);
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('${item.label} is coming soon')),
          );
        }
      },
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 50,
            height: 50,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: const Color(0xFFEFF2F7),
              borderRadius: BorderRadius.circular(14),
            ),
            child: Text(item.emoji, style: const TextStyle(fontSize: 24, height: 1)),
          ),
          const SizedBox(height: 6),
          Text(
            item.label,
            textAlign: TextAlign.center,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(fontSize: 11.5),
          ),
        ],
      ),
    );
  }
}

/// Play Store page for this build, derived from the installed package name
/// so it stays right if the application id ever changes.
Future<String> _storeWebLink() async {
  final info = await PackageInfo.fromPlatform();
  return 'https://play.google.com/store/apps/details?id=${info.packageName}';
}

/// Opens the store listing in the Play Store app, falling back to the web
/// page when the Play Store isn't installed.
Future<void> _openStoreListing(ScaffoldMessengerState messenger) async {
  final info = await PackageInfo.fromPlatform();
  final market = Uri.parse('market://details?id=${info.packageName}');
  final web = Uri.parse(await _storeWebLink());
  try {
    if (await launchUrl(market, mode: LaunchMode.externalApplication)) return;
  } catch (_) {
    // No Play Store app — fall through to the browser.
  }
  if (!await launchUrl(web, mode: LaunchMode.externalApplication)) {
    messenger.showSnackBar(const SnackBar(content: Text("Couldn't open the store. Please try again.")));
  }
}

/// Side drawer styled after the reference screenshot: a blue "View Profile"
/// header followed by white rounded tiles with tinted circular icons. The
/// general app items (settings, help, legal, share…) live here rather than
/// inside the Profile screen.
class _HomeDrawer extends ConsumerWidget {
  const _HomeDrawer({required this.societyName, required this.unit});

  final String societyName;
  final String? unit;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(authControllerProvider).valueOrNull?.user;
    final userName = user?.name ?? '';

    void go(String route) {
      Navigator.of(context).pop();
      context.push(route);
    }

    return Drawer(
      backgroundColor: const Color(0xFFF2F3F7),
      child: Column(
        children: [
          _DrawerProfileHeader(name: userName, photoUrl: user?.profilePhotoUrl, onTap: () => go('/profile')),
          Expanded(
            child: ListView(
              padding: const EdgeInsets.fromLTRB(12, 10, 12, 16),
              children: [
                _DrawerTile(icon: Icons.person_outline, accent: AppColors.accentSky, label: 'Profile', onTap: () => go('/profile')),
                _DrawerTile(
                  icon: Icons.apartment_outlined, accent: AppColors.accentIndigo,
                  label: 'My Properties',
                  subtitle: unit,
                  onTap: () => go('/my-properties'),
                ),
                _DrawerTile(
                  icon: Icons.notifications_none, accent: AppColors.warning,
                  label: 'Notification Settings',
                  onTap: () => go('/profile/notification-settings'),
                ),
                _DrawerTile(icon: Icons.language_outlined, accent: AppColors.accentTeal, label: 'Language', onTap: () => go('/profile/language')),
                _DrawerTile(icon: Icons.dark_mode_outlined, accent: AppColors.accentViolet, label: 'Theme', onTap: () => go('/profile/theme')),
                _DrawerTile(
                  icon: Icons.support_agent_outlined, accent: AppColors.success,
                  label: 'Help Line',
                  subtitle: 'Customer service 24 × 7',
                  onTap: () => go('/help-line'),
                ),
                _DrawerTile(icon: Icons.info_outline, accent: AppColors.info, label: 'About Us', onTap: () => go('/profile/about')),
                _DrawerTile(icon: Icons.gavel_outlined, accent: AppColors.accentSlate, label: 'Terms & Conditions', onTap: () => go('/profile/terms')),
                _DrawerTile(
                  icon: Icons.privacy_tip_outlined, accent: AppColors.accentRose,
                  label: 'Privacy Policy',
                  onTap: () => go('/profile/privacy-policy'),
                ),
                _DrawerTile(
                  icon: Icons.star_outline, accent: AppColors.accentAmber,
                  label: 'Rate Us',
                  onTap: () {
                    final messenger = ScaffoldMessenger.of(context);
                    Navigator.of(context).pop();
                    _openStoreListing(messenger);
                  },
                ),
                _DrawerTile(
                  icon: Icons.share_outlined, accent: AppColors.secondary,
                  label: 'Share App',
                  onTap: () async {
                    Navigator.of(context).pop();
                    final link = await _storeWebLink();
                    await Share.share(
                      'Manage your society life with FlatCare — visitors, bills, complaints and more in one app.\n\n'
                      'Download: $link',
                      subject: 'FlatCare app',
                    );
                  },
                ),
                _DrawerTile(
                  icon: Icons.cleaning_services_outlined, accent: AppColors.primary,
                  label: 'Clear Cache',
                  onTap: () async {
                    final messenger = ScaffoldMessenger.of(context);
                    Navigator.of(context).pop();
                    // Downloaded photos on disk, plus the decoded copies
                    // Flutter keeps in memory — otherwise nothing visibly
                    // changes until the app restarts.
                    await DefaultCacheManager().emptyCache();
                    PaintingBinding.instance.imageCache
                      ..clear()
                      ..clearLiveImages();
                    messenger.showSnackBar(const SnackBar(content: Text('Cache cleared.')));
                  },
                ),
                _DrawerTile(
                  icon: Icons.logout,
                  label: 'Sign out',
                  color: Theme.of(context).colorScheme.error,
                  onTap: () async {
                    // Grab the notifier before closing the drawer — by the time
                    // the confirm dialog below resolves, this drawer (and the
                    // `ref` tied to it) will already be disposed, and reading a
                    // disposed WidgetRef throws. The notifier itself is a plain
                    // object reference, so it stays safe to call after that.
                    final authNotifier = ref.read(authControllerProvider.notifier);
                    Navigator.of(context).pop();
                    final confirmed = await showFcConfirmDialog(
                      context,
                      title: 'Sign out?',
                      message: 'You will stop receiving visitor alerts on this phone until you sign in again.',
                      confirmLabel: 'Sign out',
                      icon: Icons.logout_rounded,
                      danger: true,
                    );
                    if (confirmed) {
                      await authNotifier.logout();
                    }
                  },
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _DrawerProfileHeader extends StatelessWidget {
  const _DrawerProfileHeader({required this.name, this.photoUrl, required this.onTap});

  final String name;
  final String? photoUrl;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      decoration: const BoxDecoration(
        gradient: AppColors.primaryGradient,
        borderRadius: BorderRadius.only(bottomRight: Radius.circular(28)),
      ),
      padding: EdgeInsets.fromLTRB(12, MediaQuery.of(context).padding.top + 8, 12, 10),
      child: Material(
        color: Colors.white.withValues(alpha: 0.18),
        borderRadius: BorderRadius.circular(18),
        child: InkWell(
          borderRadius: BorderRadius.circular(18),
          onTap: onTap,
          child: Padding(
            padding: const EdgeInsets.all(10),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(2),
                  decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle),
                  child: PhotoAvatar(url: photoUrl, name: name, radius: 21),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        name,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(color: Colors.white, fontSize: 15, fontWeight: FontWeight.w700),
                      ),
                      const SizedBox(height: 2),
                      Text('View Profile', style: TextStyle(color: Colors.white.withValues(alpha: 0.85), fontSize: 12)),
                    ],
                  ),
                ),
                const Icon(Icons.chevron_right, color: Colors.white, size: 20),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _DrawerTile extends StatelessWidget {
  const _DrawerTile({
    required this.icon,
    required this.label,
    required this.onTap,
    this.subtitle,
    this.accent = AppColors.primary,
    this.color,
  });

  final IconData icon;
  final String label;
  final String? subtitle;
  final VoidCallback onTap;

  /// Icon color — each tile gets its own so the menu reads at a glance.
  final Color accent;

  /// Overrides both icon and label color (used for Sign out).
  final Color? color;

  @override
  Widget build(BuildContext context) {
    final tint = color ?? accent;
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Material(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        child: InkWell(
          borderRadius: BorderRadius.circular(14),
          onTap: onTap,
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
            child: Row(
              children: [
                Container(
                  width: 32,
                  height: 32,
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                      colors: [tint.withValues(alpha: 0.75), tint],
                    ),
                    borderRadius: BorderRadius.circular(10),
                    boxShadow: [BoxShadow(color: tint.withValues(alpha: 0.30), blurRadius: 6, offset: const Offset(0, 2))],
                  ),
                  child: Icon(icon, color: Colors.white, size: 17),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        label,
                        style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.w600, color: color ?? AppTheme.brandNavy),
                      ),
                      if (subtitle != null) Text(subtitle!, style: const TextStyle(fontSize: 11, color: Colors.black54)),
                    ],
                  ),
                ),
                Icon(Icons.chevron_right, color: tint.withValues(alpha: 0.6), size: 20),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

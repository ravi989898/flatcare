import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/app_theme.dart';
import '../../auth/providers/auth_provider.dart';

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
    _MenuItem('Maintenance', '🔧', route: '/maintenance-requests'),
    _MenuItem('Complaints', '⚠️', route: '/complaints'),
  ]),
  _MenuGroup('Directory', [
    _MenuItem('Members', '👥', route: '/directory'),
    _MenuItem('Family Members', '👪', route: '/profile/family-members'),
    _MenuItem('Vehicles', '🚗', route: '/profile/vehicles'),
    _MenuItem('Important Contacts', '🚨'),
  ]),
  _MenuGroup('Interaction', [
    _MenuItem('Meetings', '💬'),
    _MenuItem('Announcement', '📣', route: '/announcements'),
    _MenuItem('Event', '📅', route: '/events'),
    _MenuItem('Voting', '🗳️'),
    _MenuItem('Amenities', '📋'),
    _MenuItem('Proposal', '📝'),
    _MenuItem('Suggestions', '💡'),
    _MenuItem('Tasks', '🗒️'),
  ]),
  _MenuGroup('Visitor', [
    _MenuItem('My Visitors', '🚪', route: '/visitors'),
    _MenuItem('My Daily Helpers', '🧹'),
    _MenuItem('Gate Keeper', '👮'),
    _MenuItem('Gate Pass', '🎫'),
    _MenuItem('Pre-Approve', '✅'),
    _MenuItem('Settings', '⚙️'),
  ]),
  _MenuGroup('My Building', [
    _MenuItem('Documents', '📄'),
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
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
                children: [
                  for (final group in _groups) ...[
                    _GroupCard(group: group),
                    const SizedBox(height: 16),
                  ],
                ],
              ),
            ),
            const _ScanQrBar(),
          ],
        ),
      ),
    );
  }
}

class _HomeHeader extends StatelessWidget {
  const _HomeHeader({required this.societyName});

  final String societyName;

  @override
  Widget build(BuildContext context) {
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
            icon: const Icon(Icons.notifications_none, color: Colors.white),
            onPressed: () => context.push('/announcements'),
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

class _ScanQrBar extends StatelessWidget {
  const _ScanQrBar();

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      top: false,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
        child: DecoratedBox(
          decoration: BoxDecoration(borderRadius: BorderRadius.circular(28), gradient: AppTheme.brandGradient),
          child: SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.transparent,
                shadowColor: Colors.transparent,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(28)),
              ),
              onPressed: () {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('QR scanning is coming soon')),
                );
              },
              icon: const Icon(Icons.qr_code_scanner),
              label: const Text('Scan QR Code'),
            ),
          ),
        ),
      ),
    );
  }
}

class _HomeDrawer extends ConsumerWidget {
  const _HomeDrawer({required this.societyName, required this.unit});

  final String societyName;
  final String? unit;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authControllerProvider);
    final userName = auth.valueOrNull?.user.name ?? '';
    final email = auth.valueOrNull?.user.email ?? '';

    return Drawer(
      child: SafeArea(
        child: Column(
          children: [
            UserAccountsDrawerHeader(
              decoration: const BoxDecoration(gradient: AppTheme.brandGradient),
              accountName: Text(userName),
              accountEmail: Text(email),
              currentAccountPicture: CircleAvatar(
                backgroundColor: Colors.white,
                child: Text(
                  userName.isNotEmpty ? userName[0].toUpperCase() : '?',
                  style: const TextStyle(color: AppTheme.brandBlue, fontWeight: FontWeight.bold),
                ),
              ),
            ),
            ListTile(
              leading: const Icon(Icons.apartment_outlined),
              title: const Text('My Properties'),
              subtitle: unit != null ? Text(unit!) : null,
              onTap: () {
                Navigator.of(context).pop();
                context.push('/my-properties');
              },
            ),
            ListTile(
              leading: const Icon(Icons.person_outline),
              title: const Text('Profile'),
              onTap: () {
                Navigator.of(context).pop();
                context.push('/profile');
              },
            ),
            const Spacer(),
            const Divider(height: 1),
            ListTile(
              leading: Icon(Icons.logout, color: Theme.of(context).colorScheme.error),
              title: Text('Sign out', style: TextStyle(color: Theme.of(context).colorScheme.error)),
              onTap: () async {
                // Grab the notifier before closing the drawer — by the time
                // the confirm dialog below resolves, this drawer (and the
                // `ref` tied to it) will already be disposed, and reading a
                // disposed WidgetRef throws. The notifier itself is a plain
                // object reference, so it stays safe to call after that.
                final authNotifier = ref.read(authControllerProvider.notifier);
                Navigator.of(context).pop();
                final confirmed = await showDialog<bool>(
                  context: context,
                  builder: (context) => AlertDialog(
                    title: const Text('Sign out?'),
                    actions: [
                      TextButton(onPressed: () => Navigator.of(context).pop(false), child: const Text('Cancel')),
                      TextButton(onPressed: () => Navigator.of(context).pop(true), child: const Text('Sign out')),
                    ],
                  ),
                );
                if (confirmed == true) {
                  await authNotifier.logout();
                }
              },
            ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );
  }
}

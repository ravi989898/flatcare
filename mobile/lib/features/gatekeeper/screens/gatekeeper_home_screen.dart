import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/widgets/fc/fc_dialogs.dart';
import '../../../core/theme/app_theme.dart';
import '../../auth/providers/auth_provider.dart';
import '../../notifications/providers/notification_providers.dart';
import '../providers/gatekeeper_providers.dart';

/// The Gatekeeper equivalent of features/home/screens/home_screen.dart —
/// same branded-header + grouped-grid visual language (see _HomeHeader /
/// _GroupCard / _MenuTile there), but with the gate-duty feature set a
/// security guard actually needs instead of a resident's.
class GatekeeperHomeScreen extends ConsumerWidget {
  const GatekeeperHomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authControllerProvider);
    final societyName = auth.valueOrNull?.society.name ?? 'FlatCare';

    return Scaffold(
      backgroundColor: const Color(0xFFF2F3F7),
      drawer: const _GatekeeperDrawer(),
      body: SafeArea(
        bottom: false,
        child: Column(
          children: [
            _GatekeeperHeader(societyName: societyName),
            Expanded(
              child: ListView(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 16),
                children: const [
                  _GateDutyPanel(),
                  SizedBox(height: 16),
                  _GroupCard(
                    title: 'Directory',
                    items: [
                      _MenuItem('Residents', '👥', route: '/directory', color: Color(0xFF8E5FE0)),
                      _MenuItem('Vehicles', '🚗', route: '/gatekeeper/vehicles', color: Color(0xFF1783C0)),
                      _MenuItem('Important Contacts', '🚨', route: '/emergency-contacts', color: Color(0xFFE0245E)),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _GatekeeperHeader extends ConsumerWidget {
  const _GatekeeperHeader({required this.societyName});

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
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  societyName,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w600),
                ),
                const Text(
                  'Gate Security',
                  style: TextStyle(color: Colors.white70, fontSize: 12),
                ),
              ],
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

class _MenuItem {
  const _MenuItem(this.label, this.emoji, {required this.route, required this.color});

  final String label;
  final String emoji;
  final String route;
  final Color color;
}

class _GroupCard extends StatelessWidget {
  const _GroupCard({required this.title, required this.items});

  final String title;
  final List<_MenuItem> items;

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
          Text(title, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
          const SizedBox(height: 12),
          GridView.count(
            crossAxisCount: 3,
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            mainAxisSpacing: 8,
            crossAxisSpacing: 4,
            childAspectRatio: 0.85,
            children: [for (final item in items) _MenuTile(item: item)],
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
      onTap: () => context.push(item.route),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 52,
            height: 52,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: item.color.withValues(alpha: 0.14),
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: item.color.withValues(alpha: 0.22)),
            ),
            child: Text(item.emoji, style: const TextStyle(fontSize: 24, height: 1)),
          ),
          const SizedBox(height: 6),
          Text(
            item.label,
            textAlign: TextAlign.center,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w600, color: item.color),
          ),
        ],
      ),
    );
  }
}

class _GatekeeperDrawer extends ConsumerWidget {
  const _GatekeeperDrawer();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authControllerProvider);
    final userName = auth.valueOrNull?.user.name ?? '';
    final phone = auth.valueOrNull?.user.phone ?? '';

    return Drawer(
      child: SafeArea(
        child: Column(
          children: [
            UserAccountsDrawerHeader(
              decoration: const BoxDecoration(gradient: AppTheme.brandGradient),
              accountName: Text(userName),
              accountEmail: Text(phone),
              currentAccountPicture: CircleAvatar(
                backgroundColor: Colors.white,
                child: Text(
                  userName.isNotEmpty ? userName[0].toUpperCase() : '?',
                  style: const TextStyle(color: AppTheme.brandBlue, fontWeight: FontWeight.bold),
                ),
              ),
            ),
            ListTile(
              leading: const Icon(Icons.badge_outlined),
              title: const Text('My Profile'),
              onTap: () {
                Navigator.of(context).pop();
                context.push('/gatekeeper/profile');
              },
            ),
            const Spacer(),
            const Divider(height: 1),
            ListTile(
              leading: Icon(Icons.logout, color: Theme.of(context).colorScheme.error),
              title: Text('Sign out', style: TextStyle(color: Theme.of(context).colorScheme.error)),
              onTap: () async {
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
            const SizedBox(height: 8),
          ],
        ),
      ),
    );
  }
}

/// Gate Duty: the five things a guard does all day, in order of use -
/// the Walk-in Check-in banner, then Pending Requests, Visitor Log, Gate
/// Passes and Closed Houses, each with its own color and a live count from
/// /guard/summary.
class _GateDutyPanel extends ConsumerWidget {
  const _GateDutyPanel();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final summary = ref.watch(guardSummaryProvider).valueOrNull;

    Future<void> open(String route) async {
      await context.push(route);
      ref.invalidate(guardSummaryProvider);
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        const Padding(
          padding: EdgeInsets.only(left: 4, bottom: 10),
          child: Text('Gate Duty', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: AppColors.textPrimary)),
        ),
        _WalkInBanner(onTap: () => open('/gatekeeper/visitors/check-in')),
        const SizedBox(height: 12),
        GridView.count(
          crossAxisCount: 2,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          mainAxisSpacing: 12,
          crossAxisSpacing: 12,
          childAspectRatio: 1.55,
          children: [
            _DutyTile(
              label: 'Pending Requests',
              caption: 'Today · awaiting resident approval',
              icon: Icons.hourglass_top_rounded,
              color: const Color(0xFFF08A00),
              tintCaption: true,
              count: summary?.pendingRequests,
              onTap: () => open('/gatekeeper/requests'),
            ),
            _DutyTile(
              label: 'Visitor Log',
              caption: '${summary?.insideNow ?? 0} inside now',
              icon: Icons.assignment_rounded,
              color: const Color(0xFF1D5FD1),
              count: summary?.visitorsToday,
              onTap: () => open('/gatekeeper/visitors'),
            ),
            _DutyTile(
              label: 'Gate Passes',
              caption: 'Issued by residents',
              icon: Icons.confirmation_number_rounded,
              color: const Color(0xFF6A4BD8),
              count: summary?.activePasses,
              onTap: () => open('/gatekeeper/passes'),
            ),
            _DutyTile(
              label: 'Closed Houses',
              caption: 'No entry allowed',
              icon: Icons.lock_rounded,
              color: const Color(0xFFE02434),
              alert: true,
              count: summary?.closedHouses,
              onTap: () => open('/gatekeeper/closed-houses'),
            ),
          ],
        ),
      ],
    );
  }
}

/// Full-width blue banner for the guard's most used action, with a small
/// boom-barrier-and-car scene on the left.
class _WalkInBanner extends StatelessWidget {
  const _WalkInBanner({required this.onTap});

  final VoidCallback onTap;

  static const _blue = Color(0xFF1560D8);

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: Ink(
        decoration: BoxDecoration(
          gradient: const LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [Color(0xFF3FA2F6), _blue],
          ),
          borderRadius: BorderRadius.circular(20),
          boxShadow: [BoxShadow(color: _blue.withValues(alpha: 0.3), blurRadius: 14, offset: const Offset(0, 6))],
        ),
        child: InkWell(
          borderRadius: BorderRadius.circular(20),
          onTap: onTap,
          child: ClipRRect(
            borderRadius: BorderRadius.circular(20),
            child: SizedBox(
              height: 96,
              child: Row(
                children: [
                  const _GateScene(),
                  Container(
                    width: 46,
                    height: 46,
                    decoration: BoxDecoration(
                      color: const Color(0xFF1BC5B4),
                      shape: BoxShape.circle,
                      border: Border.all(color: Colors.white.withValues(alpha: 0.5), width: 2),
                    ),
                    child: const Icon(Icons.person_add_alt_1_rounded, color: Colors.white, size: 24),
                  ),
                  const SizedBox(width: 12),
                  const Expanded(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Walk-in Check-in',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 19),
                        ),
                        SizedBox(height: 2),
                        Text(
                          'Register a visitor at the gate',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(color: Colors.white, fontSize: 12.5, fontWeight: FontWeight.w500),
                        ),
                      ],
                    ),
                  ),
                  Container(
                    width: 38,
                    height: 38,
                    margin: const EdgeInsets.only(left: 8, right: 14),
                    decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle),
                    child: const Icon(Icons.chevron_right_rounded, color: _blue, size: 26),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

/// A raised red-and-white boom barrier, some bushes and a car.
class _GateScene extends StatelessWidget {
  const _GateScene();

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 92,
      height: 96,
      child: Stack(
        clipBehavior: Clip.none,
        children: [
          Positioned(
            left: 12,
            bottom: 14,
            child: Container(
              width: 12,
              height: 46,
              decoration: BoxDecoration(color: const Color(0xFFF26B1D), borderRadius: BorderRadius.circular(3)),
            ),
          ),
          Positioned(
            left: 16,
            top: 30,
            child: Transform.rotate(
              angle: -0.45,
              alignment: Alignment.centerLeft,
              child: Container(
                width: 76,
                height: 8,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(4),
                  gradient: const LinearGradient(
                    colors: [Color(0xFFE53935), Color(0xFFE53935), Colors.white, Colors.white],
                    stops: [0, .5, .5, 1],
                    end: Alignment(-0.8, 0),
                    tileMode: TileMode.repeated,
                  ),
                ),
              ),
            ),
          ),
          Positioned(left: -12, bottom: -12, child: _bush(36)),
          Positioned(left: 14, bottom: -16, child: _bush(28)),
          const Positioned(
            right: 2,
            bottom: 8,
            child: Icon(Icons.directions_car_filled_rounded, color: Colors.white, size: 46),
          ),
        ],
      ),
    );
  }

  static Widget _bush(double size) => Container(
        width: size,
        height: size,
        decoration: const BoxDecoration(color: Color(0xFF2E9D57), shape: BoxShape.circle),
      );
}

/// Light, tinted gate-duty tile: icon bubble + live count and a chevron on
/// top, the label and caption below.
class _DutyTile extends StatelessWidget {
  const _DutyTile({
    required this.label,
    required this.caption,
    required this.icon,
    required this.color,
    required this.onTap,
    this.count,
    this.alert = false,
    this.tintCaption = false,
  });

  final String label;
  final String caption;
  final IconData icon;
  final Color color;
  final VoidCallback onTap;
  final int? count;
  // Closed Houses: count, label and caption all in the tile color.
  final bool alert;
  final bool tintCaption;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: Ink(
        decoration: BoxDecoration(
          color: Color.alphaBlend(color.withValues(alpha: 0.07), Colors.white),
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: Colors.white, width: 2),
          boxShadow: [BoxShadow(color: color.withValues(alpha: 0.12), blurRadius: 10, offset: const Offset(0, 4))],
        ),
        child: InkWell(
          borderRadius: BorderRadius.circular(18),
          onTap: onTap,
          child: Padding(
            padding: const EdgeInsets.fromLTRB(12, 12, 8, 10),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      width: 42,
                      height: 42,
                      decoration: BoxDecoration(color: color.withValues(alpha: 0.15), shape: BoxShape.circle),
                      child: Icon(icon, color: color, size: 22),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        count == null ? '-' : '$count',
                        maxLines: 1,
                        style: TextStyle(
                          color: alert ? color : AppColors.textPrimary,
                          fontSize: 28,
                          fontWeight: FontWeight.w800,
                          height: 1,
                        ),
                      ),
                    ),
                    Icon(Icons.chevron_right_rounded, color: color, size: 24),
                  ],
                ),
                const Spacer(),
                Text(
                  label,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(color: alert ? color : AppColors.textPrimary, fontWeight: FontWeight.w800, fontSize: 15),
                ),
                const SizedBox(height: 2),
                Text(
                  caption,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    color: alert || tintCaption ? color : AppColors.textSecondary,
                    fontSize: 11.5,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

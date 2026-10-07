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

/// Gate Duty: the five things a guard does all day, in order of use —
/// 1 Walk-in Check-in (full width), then 2 Pending Requests, 3 Visitor Log,
/// 4 Gate Passes and 5 Closed Houses, each with its own color and a live
/// count from /guard/summary.
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
        _DutyTile(
          number: 1,
          label: 'Walk-in Check-in',
          caption: 'Register a visitor at the gate',
          icon: Icons.person_add_alt_1_rounded,
          colors: const [Color(0xFF34C38F), AppColors.success],
          wide: true,
          onTap: () => open('/gatekeeper/visitors/check-in'),
        ),
        const SizedBox(height: 12),
        GridView.count(
          crossAxisCount: 2,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          mainAxisSpacing: 12,
          crossAxisSpacing: 12,
          childAspectRatio: 1.08,
          children: [
            _DutyTile(
              number: 2,
              label: 'Pending Requests',
              caption: 'Today · awaiting resident',
              icon: Icons.hourglass_top_rounded,
              colors: const [Color(0xFFF6B23C), AppColors.warning],
              count: summary?.pendingRequests,
              onTap: () => open('/gatekeeper/requests'),
            ),
            _DutyTile(
              number: 3,
              label: 'Visitor Log',
              caption: '${summary?.insideNow ?? 0} inside now',
              icon: Icons.menu_book_rounded,
              colors: const [AppColors.secondary, AppColors.primary],
              count: summary?.visitorsToday,
              onTap: () => open('/gatekeeper/visitors'),
            ),
            _DutyTile(
              number: 4,
              label: 'Gate Passes',
              caption: 'Issued by residents',
              icon: Icons.confirmation_number_rounded,
              colors: const [Color(0xFF7B7BE8), AppColors.accentIndigo],
              count: summary?.activePasses,
              onTap: () => open('/gatekeeper/passes'),
            ),
            _DutyTile(
              number: 5,
              label: 'Closed Houses',
              caption: 'No entry allowed',
              icon: Icons.lock_rounded,
              colors: const [Color(0xFFEF6B75), AppColors.danger],
              count: summary?.closedHouses,
              onTap: () => open('/gatekeeper/closed-houses'),
            ),
          ],
        ),
      ],
    );
  }
}

class _DutyTile extends StatelessWidget {
  const _DutyTile({
    required this.number,
    required this.label,
    required this.caption,
    required this.icon,
    required this.colors,
    required this.onTap,
    this.count,
    this.wide = false,
  });

  final int number;
  final String label;
  final String caption;
  final IconData icon;
  final List<Color> colors;
  final VoidCallback onTap;
  final int? count;
  final bool wide;

  @override
  Widget build(BuildContext context) {
    final iconBox = Container(
      width: wide ? 54 : 46,
      height: wide ? 54 : 46,
      decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.22), borderRadius: BorderRadius.circular(15)),
      child: Icon(icon, color: Colors.white, size: wide ? 30 : 26),
    );
    final numberDot = Container(
      width: 24,
      height: 24,
      alignment: Alignment.center,
      decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.25), shape: BoxShape.circle),
      child: Text('$number', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 12)),
    );
    const titleStyle = TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 16);
    final captionStyle = TextStyle(color: Colors.white.withValues(alpha: 0.92), fontSize: 12.5, fontWeight: FontWeight.w500);

    return Material(
      color: Colors.transparent,
      child: Ink(
        decoration: BoxDecoration(
          gradient: LinearGradient(begin: Alignment.topLeft, end: Alignment.bottomRight, colors: colors),
          borderRadius: BorderRadius.circular(20),
          boxShadow: [BoxShadow(color: colors.last.withValues(alpha: 0.3), blurRadius: 12, offset: const Offset(0, 6))],
        ),
        child: InkWell(
          borderRadius: BorderRadius.circular(20),
          onTap: onTap,
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: wide
                ? Row(
                    children: [
                      iconBox,
                      const SizedBox(width: 14),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(label, style: titleStyle.copyWith(fontSize: 18)),
                            const SizedBox(height: 2),
                            Text(caption, style: captionStyle),
                          ],
                        ),
                      ),
                      const Icon(Icons.arrow_forward_rounded, color: Colors.white),
                    ],
                  )
                : Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          iconBox,
                          const Spacer(),
                          if (count != null)
                            Text('$count', style: const TextStyle(color: Colors.white, fontSize: 28, fontWeight: FontWeight.w800, height: 1))
                          else
                            numberDot,
                        ],
                      ),
                      const Spacer(),
                      Text(label, maxLines: 1, overflow: TextOverflow.ellipsis, style: titleStyle),
                      const SizedBox(height: 2),
                      Text(caption, maxLines: 1, overflow: TextOverflow.ellipsis, style: captionStyle),
                    ],
                  ),
          ),
        ),
      ),
    );
  }
}

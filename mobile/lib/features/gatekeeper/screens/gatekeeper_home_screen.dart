import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/app_theme.dart';
import '../../auth/providers/auth_provider.dart';
import '../data/duty_repository.dart';
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
                  _DutyStatusCard(),
                  SizedBox(height: 16),
                  _GroupCard(
                    title: 'Gate Duty',
                    items: [
                      _MenuItem('Visitor Log', '🚪', route: '/gatekeeper/visitors', color: Color(0xFF1E9CE0)),
                      _MenuItem('Walk-in Check-in', '📝', route: '/gatekeeper/visitors/check-in', color: Color(0xFF2AB98A)),
                      _MenuItem(
                        'Expected Visitors',
                        '⏳',
                        route: '/gatekeeper/visitors',
                        initialStatus: 'pending',
                        color: Color(0xFFF5A623),
                      ),
                    ],
                  ),
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

class _GatekeeperHeader extends StatelessWidget {
  const _GatekeeperHeader({required this.societyName});

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
            icon: const Icon(Icons.notifications_none, color: Colors.white),
            onPressed: () => context.push('/notifications'),
          ),
        ],
      ),
    );
  }
}

/// On/off-duty toggle at the top of the home screen — the guard's most
/// frequent action after logging in.
class _DutyStatusCard extends ConsumerStatefulWidget {
  const _DutyStatusCard();

  @override
  ConsumerState<_DutyStatusCard> createState() => _DutyStatusCardState();
}

class _DutyStatusCardState extends ConsumerState<_DutyStatusCard> {
  bool _isUpdating = false;

  Future<void> _startShift(String shift) async {
    setState(() => _isUpdating = true);
    try {
      await ref.read(dutyRepositoryProvider).start(shift);
      ref.invalidate(dutyStatusProvider);
    } finally {
      if (mounted) setState(() => _isUpdating = false);
    }
  }

  Future<void> _endShift() async {
    setState(() => _isUpdating = true);
    try {
      await ref.read(dutyRepositoryProvider).end();
      ref.invalidate(dutyStatusProvider);
    } finally {
      if (mounted) setState(() => _isUpdating = false);
    }
  }

  static const _onDutyGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [Color(0xFF2AB930), Color(0xFF1E9CE0)],
  );
  static const _offDutyGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [Color(0xFF5B6472), Color(0xFF0B1E3D)],
  );

  @override
  Widget build(BuildContext context) {
    final duty = ref.watch(dutyStatusProvider);

    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: duty.valueOrNull?.onDuty ?? false ? _onDutyGradient : _offDutyGradient,
        borderRadius: BorderRadius.circular(18),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.12), blurRadius: 14, offset: const Offset(0, 6))],
      ),
      child: duty.when(
        loading: () => const SizedBox(
          height: 40,
          child: Center(child: CircularProgressIndicator(strokeWidth: 2, valueColor: AlwaysStoppedAnimation(Colors.white))),
        ),
        error: (error, stackTrace) => Row(
          children: [
            const Expanded(
              child: Text("Couldn't load duty status.", style: TextStyle(color: Colors.white)),
            ),
            TextButton(
              onPressed: () => ref.invalidate(dutyStatusProvider),
              child: const Text('Retry', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700)),
            ),
          ],
        ),
        data: (status) {
          final onDuty = status.onDuty;

          return Row(
            children: [
              Container(
                width: 40,
                height: 40,
                alignment: Alignment.center,
                decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.18), shape: BoxShape.circle),
                child: Text(onDuty ? '🛡️' : '💤', style: const TextStyle(fontSize: 19)),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      onDuty ? 'On Duty · ${_shiftLabel(status.currentShift)}' : 'Off Duty',
                      style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 16, color: Colors.white),
                    ),
                    Text(
                      onDuty ? 'Assigned shift: ${_shiftLabel(status.assignedShift)}' : 'Tap Start to begin your shift',
                      style: TextStyle(color: Colors.white.withValues(alpha: 0.85), fontSize: 12),
                    ),
                  ],
                ),
              ),
              if (_isUpdating)
                const SizedBox(
                  height: 20,
                  width: 20,
                  child: CircularProgressIndicator(strokeWidth: 2, valueColor: AlwaysStoppedAnimation(Colors.white)),
                )
              else if (onDuty)
                OutlinedButton(
                  style: OutlinedButton.styleFrom(foregroundColor: Colors.white, side: const BorderSide(color: Colors.white70)),
                  onPressed: _endShift,
                  child: const Text('End Shift'),
                )
              else
                ElevatedButton(
                  style: ElevatedButton.styleFrom(backgroundColor: Colors.white, foregroundColor: const Color(0xFF0B1E3D)),
                  onPressed: () => _startShift(status.assignedShift),
                  child: const Text('Start Shift'),
                ),
            ],
          );
        },
      ),
    );
  }

  String _shiftLabel(String? shift) => switch (shift) {
        'day' => 'Day',
        'night' => 'Night',
        _ => '-',
      };
}

class _MenuItem {
  const _MenuItem(this.label, this.emoji, {required this.route, required this.color, this.initialStatus});

  final String label;
  final String emoji;
  final String route;
  final Color color;
  // Passed as `extra` to GatekeeperVisitorListScreen to preselect a tab
  // (e.g. "Expected Visitors" opening straight to the pending tab).
  final String? initialStatus;
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
      onTap: () => context.push(item.route, extra: item.initialStatus),
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

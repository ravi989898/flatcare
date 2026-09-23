import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/fc/fc_dialogs.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/async_view.dart';
import '../../auth/providers/auth_provider.dart';
import '../data/duty_status.dart';
import '../providers/gatekeeper_providers.dart';

/// A guard's own info — deliberately not the resident ProfileScreen (which
/// is full of resident-only sections: family members, own vehicles, bills,
/// maintenance, etc. that don't apply here).
class GatekeeperProfileScreen extends ConsumerWidget {
  const GatekeeperProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final duty = ref.watch(dutyStatusProvider);

    return Scaffold(
      backgroundColor: const Color(0xFFF2F3F7),
      appBar: AppBar(
        title: const Text('My Profile'),
        foregroundColor: Colors.white,
        flexibleSpace: const DecoratedBox(decoration: BoxDecoration(gradient: AppTheme.brandGradient)),
      ),
      body: AsyncView<DutyStatus>(
        value: duty,
        onRetry: () => ref.invalidate(dutyStatusProvider),
        builder: (context, status) => ListView(
          padding: EdgeInsets.zero,
          children: [
            Container(
              width: double.infinity,
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 28),
              decoration: const BoxDecoration(gradient: AppTheme.brandGradient),
              child: Column(
                children: [
                  CircleAvatar(
                    radius: 38,
                    backgroundColor: Colors.white,
                    child: Text(
                      status.name.isNotEmpty ? status.name[0].toUpperCase() : '?',
                      style: const TextStyle(color: AppTheme.brandBlueDark, fontSize: 30, fontWeight: FontWeight.bold),
                    ),
                  ),
                  const SizedBox(height: 12),
                  Text(status.name, style: const TextStyle(fontSize: 19, fontWeight: FontWeight.w800, color: Colors.white)),
                  Text('Security Guard', style: TextStyle(color: Colors.white.withValues(alpha: 0.85), fontSize: 12.5)),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                children: [
                  Transform.translate(
                    offset: const Offset(0, -24),
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(16),
                        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.06), blurRadius: 10, offset: const Offset(0, 4))],
                      ),
                      child: Column(
                        children: [
                          ListTile(
                            leading: const CircleAvatar(backgroundColor: Color(0x1A1E9CE0), child: Icon(Icons.phone_outlined, color: AppTheme.brandBlueDark)),
                            title: const Text('Phone'),
                            subtitle: Text(status.phone),
                          ),
                          const Divider(height: 1, indent: 16, endIndent: 16),
                          ListTile(
                            leading: const CircleAvatar(backgroundColor: Color(0x1AF5A623), child: Icon(Icons.schedule_outlined, color: Color(0xFFB3760F))),
                            title: const Text('Assigned Shift'),
                            subtitle: Text(status.assignedShift == 'night' ? 'Night' : 'Day'),
                          ),
                          const Divider(height: 1, indent: 16, endIndent: 16),
                          ListTile(
                            leading: CircleAvatar(
                              backgroundColor: (status.onDuty ? Colors.green : Colors.grey).withValues(alpha: 0.14),
                              child: Icon(Icons.circle, size: 12, color: status.onDuty ? Colors.green : Colors.grey),
                            ),
                            title: const Text('Duty Status'),
                            subtitle: Text(status.onDuty ? 'On duty' : 'Off duty'),
                          ),
                        ],
                      ),
                    ),
                  ),
                  OutlinedButton.icon(
                    style: OutlinedButton.styleFrom(
                      foregroundColor: const Color(0xFFE0245E),
                      side: const BorderSide(color: Color(0xFFE0245E)),
                      minimumSize: const Size.fromHeight(48),
                    ),
                    icon: const Icon(Icons.logout),
                    label: const Text('Sign out'),
                    onPressed: () async {
                      final authNotifier = ref.read(authControllerProvider.notifier);
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
      ),
    );
  }
}

import 'package:flutter/material.dart';

import '../../../core/push/alert_setup.dart';

/// Walks the resident through the phone settings a visitor request needs
/// to arrive with the app closed. Opened by itself after sign-in while
/// something is missing (PushService), and from Notification Settings.
/// Each step re-checks when the resident comes back from Android's settings.
class AlertSetupScreen extends StatefulWidget {
  const AlertSetupScreen({super.key});

  @override
  State<AlertSetupScreen> createState() => _AlertSetupScreenState();
}

class _AlertSetupScreenState extends State<AlertSetupScreen> with WidgetsBindingObserver {
  AlertSetupStatus? _status;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _refresh();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) _refresh();
  }

  Future<void> _refresh() async {
    final status = await AlertSetup.status();
    if (mounted) setState(() => _status = status);
  }

  Future<void> _open(Future<void> Function() action) async {
    await action();
    // Auto-start counts as done once opened, so update without waiting for a resume.
    await _refresh();
  }

  @override
  Widget build(BuildContext context) {
    final status = _status;

    return Scaffold(
      appBar: AppBar(title: const Text('Visitor Alert Setup')),
      body: status == null
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.symmetric(vertical: 8),
              children: [
                ListTile(
                  leading: Icon(
                    status.complete ? Icons.verified_outlined : Icons.info_outline,
                    color: status.complete ? Colors.green : Colors.orange,
                  ),
                  title: Text(status.complete ? 'All set' : 'A few settings are needed'),
                  subtitle: Text(status.complete
                      ? 'Visitor requests will reach you even when the app is closed.'
                      : 'Without these, your phone may block visitor requests from the gate while the app is closed.'),
                ),
                const Divider(),
                _Step(
                  done: status.notifications,
                  title: 'Allow notifications',
                  subtitle: 'Turn on notifications for FlatCare, including "Visitor requests".',
                  onTap: () => _open(AlertSetup.openNotifications),
                ),
                _Step(
                  done: status.battery,
                  title: 'Remove battery restriction',
                  subtitle: 'Tap Allow, or choose "Unrestricted" / "Don\'t optimise" for FlatCare.',
                  onTap: () => _open(AlertSetup.openBattery),
                ),
                if (status.autoStartNeeded)
                  _Step(
                    done: status.autoStartDone,
                    title: 'Turn on Auto-start',
                    subtitle: '${status.brand} phones stop apps that are closed. Turn on the switch next to '
                        'FlatCare (it may be called Auto-start, Auto-launch or Background start).',
                    onTap: () => _open(AlertSetup.openAutoStart),
                  ),
                if (status.fullScreenNeeded)
                  _Step(
                    done: status.fullScreen,
                    title: 'Allow full-screen alerts',
                    subtitle: 'Shows a visitor at the gate like an incoming call on a locked phone.',
                    onTap: () => _open(AlertSetup.openFullScreen),
                  ),
                if (status.lockScreenNeeded)
                  _Step(
                    done: status.lockScreenDone,
                    title: 'Allow "Show on lock screen"',
                    subtitle: '${status.brand} phones hide the visitor popup on a locked phone. Under Permissions '
                        '(or Other permissions), allow "Show on lock screen" and "Display pop-up windows" for FlatCare.',
                    onTap: () => _open(AlertSetup.openLockScreen),
                  ),
              ],
            ),
    );
  }
}

class _Step extends StatelessWidget {
  const _Step({required this.done, required this.title, required this.subtitle, required this.onTap});

  final bool done;
  final String title;
  final String subtitle;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: Icon(
        done ? Icons.check_circle : Icons.radio_button_unchecked,
        color: done ? Colors.green : Theme.of(context).colorScheme.outline,
      ),
      title: Text(title),
      subtitle: Text(subtitle),
      isThreeLine: true,
      trailing: done ? null : FilledButton.tonal(onPressed: onTap, child: const Text('Fix')),
      onTap: onTap,
    );
  }
}

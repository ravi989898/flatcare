import 'package:flutter/material.dart';

/// Local on/off toggles only — there's no push-notification infra behind
/// this yet (the app polls GET /notifications), so these don't gate
/// anything server-side today. See the implementation plan's Profile-menu
/// scope decision for why this stays a lightweight, self-contained screen.
class NotificationSettingsScreen extends StatefulWidget {
  const NotificationSettingsScreen({super.key});

  @override
  State<NotificationSettingsScreen> createState() => _NotificationSettingsScreenState();
}

class _NotificationSettingsScreenState extends State<NotificationSettingsScreen> {
  bool _maintenanceDue = true;
  bool _requestStatus = true;
  bool _notices = true;
  bool _visitors = true;
  bool _events = true;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Notification Settings')),
      body: ListView(
        children: [
          SwitchListTile(
            title: const Text('Maintenance Due Reminders'),
            value: _maintenanceDue,
            onChanged: (value) => setState(() => _maintenanceDue = value),
          ),
          SwitchListTile(
            title: const Text('Service Request Updates'),
            value: _requestStatus,
            onChanged: (value) => setState(() => _requestStatus = value),
          ),
          SwitchListTile(
            title: const Text('Notices & Circulars'),
            value: _notices,
            onChanged: (value) => setState(() => _notices = value),
          ),
          SwitchListTile(
            title: const Text('Visitor Arrivals'),
            value: _visitors,
            onChanged: (value) => setState(() => _visitors = value),
          ),
          SwitchListTile(
            title: const Text('Events & Meetings'),
            value: _events,
            onChanged: (value) => setState(() => _events = value),
          ),
        ],
      ),
    );
  }
}

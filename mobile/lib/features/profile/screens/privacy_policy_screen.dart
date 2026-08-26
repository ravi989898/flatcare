import 'package:flutter/material.dart';

class PrivacyPolicyScreen extends StatelessWidget {
  const PrivacyPolicyScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Privacy Policy')),
      body: const SingleChildScrollView(
        padding: EdgeInsets.all(16),
        child: Text(
          'FlatCare collects only the information your society admin enters on your behalf '
          '(name, contact details, flat/vehicle/family records) and what you submit yourself '
          '(service requests, complaints, visitor invites). This data is used solely to run '
          'your society\'s day-to-day operations and is never sold or shared with third parties. '
          'Contact your society office for any data access or deletion requests.',
          style: TextStyle(height: 1.5),
        ),
      ),
    );
  }
}

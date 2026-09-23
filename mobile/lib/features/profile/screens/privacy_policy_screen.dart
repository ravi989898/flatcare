import 'package:flutter/material.dart';

import '../widgets/legal_page.dart';

class PrivacyPolicyScreen extends StatelessWidget {
  const PrivacyPolicyScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const LegalPage(
      title: 'Privacy Policy',
      icon: Icons.privacy_tip_rounded,
      effectiveDate: '1 September 2026',
      summary: 'FlatCare only keeps what your society needs to run day to day. We never sell your data, and you '
          'can ask for it to be corrected or deleted at any time.',
      sections: [
        LegalSection(
          title: 'What we collect',
          icon: Icons.inventory_2_outlined,
          points: [
            'Account details: your name, phone number, email and profile photo.',
            'Flat details: your block, flat number and whether you are an owner or tenant.',
            'Household records you add: family members and vehicles.',
            'Activity: visitor requests, gate entries, complaints, bills and payments.',
            'Device information needed to send you notifications (a push token).',
          ],
        ),
        LegalSection(
          title: 'How we use it',
          icon: Icons.settings_suggest_outlined,
          points: [
            'To let the gate verify and notify you about visitors.',
            'To show your bills, notices, events and polls.',
            'To help your committee resolve complaints and requests.',
            'To keep the service secure and fix problems.',
          ],
        ),
        LegalSection(
          title: 'Who can see your information',
          icon: Icons.visibility_outlined,
          points: [
            'Your society admin and committee, to manage the society.',
            'Gatekeepers see your name, flat and phone number so they can reach you about visitors.',
            'Other residents see only your name and flat in the directory — phone numbers and emails are masked.',
            'We never sell or rent your data to anyone.',
          ],
        ),
        LegalSection(
          title: 'Notifications',
          icon: Icons.notifications_active_outlined,
          points: [
            'We use Firebase Cloud Messaging to deliver visitor alerts and reminders to your phone.',
            'Signing out removes this device from your account so it stops receiving your alerts.',
            'You can manage notification preferences under Profile › Notification Settings.',
          ],
        ),
        LegalSection(
          title: 'Security & retention',
          icon: Icons.lock_outline_rounded,
          points: [
            'Data is sent over encrypted connections and stored on secured servers.',
            'Each society\'s data is kept separate from every other society.',
            'Records are kept while you live in the society and as long as your society needs them for its accounts.',
          ],
        ),
        LegalSection(
          title: 'Your choices',
          icon: Icons.verified_user_outlined,
          points: [
            'Update your profile, family members and vehicles in the app at any time.',
            'Ask your society office or FlatCare support for a copy of your data, or to delete it.',
            'We will tell you in the app before any important change to this policy.',
          ],
        ),
      ],
    );
  }
}

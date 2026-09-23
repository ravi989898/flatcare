import 'package:flutter/material.dart';

import '../widgets/legal_page.dart';

class TermsScreen extends StatelessWidget {
  const TermsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const LegalPage(
      title: 'Terms & Conditions',
      icon: Icons.gavel_rounded,
      effectiveDate: '1 September 2026',
      summary: 'The simple rules for using FlatCare in your society — what you can expect from us, and what we '
          'expect from every resident, committee member and gatekeeper.',
      sections: [
        LegalSection(
          title: 'Using FlatCare',
          icon: Icons.handshake_outlined,
          intro: 'FlatCare is provided to you through your housing society. By signing in you agree to these terms '
              'and to your society\'s own bye-laws.',
          points: [
            'Your account is created and managed by your society admin.',
            'Use the app only for genuine society matters — visitors, bills, notices, complaints and your household records.',
            'Keep your OTP and password private. You are responsible for activity on your account.',
          ],
        ),
        LegalSection(
          title: 'Your household information',
          icon: Icons.family_restroom_rounded,
          points: [
            'Keep your family members, vehicles and contact details accurate and up to date.',
            'Only add people who actually live in or regularly visit your flat.',
            'Your society admin may correct or remove records that are wrong or misleading.',
          ],
        ),
        LegalSection(
          title: 'Visitors & gate security',
          icon: Icons.shield_outlined,
          points: [
            'Approve or reject visitor requests only for people you know or expect.',
            'Gate passes and pre-approvals are for your own guests — do not share them with others.',
            'Security staff may still stop any visitor for the safety of the society.',
          ],
        ),
        LegalSection(
          title: 'Maintenance & payments',
          icon: Icons.receipt_long_rounded,
          points: [
            'Bills and due dates are set by your society, not by FlatCare.',
            'Online payments are processed by our payment partner; receipts appear in the app once confirmed.',
            'Refunds and disputes are handled by your society office.',
          ],
        ),
        LegalSection(
          title: 'Acceptable use',
          icon: Icons.rule_rounded,
          intro: 'To keep FlatCare safe and fair for everyone, you must not:',
          points: [
            'Post false complaints, abusive content or spam in notices, polls or requests.',
            'Try to view or change another resident\'s data.',
            'Misuse visitor approvals or attempt to bypass gate security.',
            'Interfere with, copy or reverse-engineer the app or its servers.',
          ],
        ),
        LegalSection(
          title: 'Suspension & changes',
          icon: Icons.update_rounded,
          points: [
            'Your society admin may suspend an account that breaks these terms.',
            'We may update these terms as FlatCare grows. We will let you know in the app before important changes take effect.',
            'Continuing to use FlatCare after an update means you accept the new terms.',
          ],
        ),
      ],
    );
  }
}

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
      summary: 'Your privacy matters to us. This policy explains what information FlatCare collects, why we need '
          'it, who can see it, and the choices you have. We never sell your data.',
      sections: [
        LegalSection(
          title: 'Introduction',
          icon: Icons.waving_hand_rounded,
          blocks: [
            LegalBlock.p('FlatCare ("FlatCare", "we", "us" or "our") provides a society management app and website '
                '(the "Service") to housing societies and their residents, committee members and security staff.'),
            LegalBlock.p('This Privacy Policy explains how we collect, use, share and protect your personal '
                'information when you use the Service. By using FlatCare you agree to the practices described here. '
                'This policy should be read together with our Terms & Conditions.'),
            LegalBlock.note('We collect only what your society needs to run day to day, and we never sell or rent '
                'your personal information to anyone.'),
          ],
        ),
        LegalSection(
          title: 'Information We Collect',
          icon: Icons.inventory_2_rounded,
          blocks: [
            LegalBlock.h('Account information'),
            LegalBlock.p('When your society adds you to FlatCare we receive your name, mobile number, flat and block, '
                'and whether you are an owner, tenant, committee member or security guard. You may also add a '
                'profile photo.'),
            LegalBlock.h('Household information'),
            LegalBlock.p('Details you choose to add about your household, such as family members (name, relation and '
                'optional phone number) and vehicles (vehicle number and type).'),
            LegalBlock.h('Activity in the app'),
            LegalBlock.bullets([
              'Visitor requests, approvals, gate passes and entry / exit times.',
              'Visitor names, phone numbers, photos and vehicle numbers recorded at the gate.',
              'Complaints and requests, including any photos you attach.',
              'Maintenance bills, payment status and receipts.',
              'Poll votes and responses to notices and events.',
            ]),
            LegalBlock.h('Device & usage information'),
            LegalBlock.p('When you use the app we automatically receive some technical information, such as your '
                'device model, operating system version, app version, and a notification token that lets us send '
                'alerts to your phone. We may also keep basic logs such as the time of a request and error reports, '
                'so we can keep the Service secure and fix problems.'),
          ],
        ),
        LegalSection(
          title: 'How We Use Your Information',
          icon: Icons.settings_suggest_rounded,
          blocks: [
            LegalBlock.p('FlatCare uses the information it collects for these purposes:'),
            LegalBlock.bullets([
              'To sign you in securely with an OTP sent to your mobile number.',
              'To let security guards verify visitors and notify you when someone arrives for you.',
              'To show your maintenance bills, record your payments and generate receipts.',
              'To share notices, polls and events from your managing committee.',
              'To help your committee track and resolve complaints and requests.',
              'To provide customer support when you contact us.',
              'To keep the Service safe, prevent misuse and fix technical issues.',
              'To understand how features are used so we can improve FlatCare.',
            ]),
          ],
        ),
        LegalSection(
          title: 'Who Can See Your Information',
          icon: Icons.visibility_rounded,
          blocks: [
            LegalBlock.p('Your information is shared only inside your own society, and only with the people who need it:'),
            LegalBlock.h('Your society admin & committee'),
            LegalBlock.p('They can see resident, household, visitor, complaint and payment records so they can manage '
                'the society.'),
            LegalBlock.h('Security guards'),
            LegalBlock.p('Gatekeepers can see your name, flat and phone number so they can contact you about visitors, '
                'deliveries and emergencies.'),
            LegalBlock.h('Other residents'),
            LegalBlock.p('In the society directory other residents see your name, block and flat. Your phone number is '
                'partly hidden from them.'),
            LegalBlock.note('Residents of other societies can never see your information. Each society\'s data is '
                'kept separate.'),
          ],
        ),
        LegalSection(
          title: 'Service Providers',
          icon: Icons.hub_rounded,
          blocks: [
            LegalBlock.p('We work with a small number of trusted companies that help us run FlatCare. They may process '
                'your information only on our behalf and only for the task we give them:'),
            LegalBlock.bullets([
              'Payment processing — Razorpay handles online maintenance payments. Your card, UPI and bank details '
                  'go directly to them and are never stored by FlatCare.',
              'Push notifications — Google Firebase Cloud Messaging delivers visitor alerts and reminders to your phone.',
              'SMS delivery — an SMS provider sends the one-time passwords (OTPs) you use to sign in.',
              'Hosting — secure cloud servers store the Service\'s data.',
            ]),
          ],
        ),
        LegalSection(
          title: 'Legal Requirements',
          icon: Icons.gavel_rounded,
          blocks: [
            LegalBlock.p('FlatCare may disclose your personal information if we honestly believe it is necessary to:'),
            LegalBlock.numbered([
              'Follow the law, a court order or a lawful request from a government authority.',
              'Protect the rights, property or safety of FlatCare, its users or the public.',
              'Prevent or investigate fraud, security problems or misuse of the Service.',
            ]),
          ],
        ),
        LegalSection(
          title: 'Notifications',
          icon: Icons.notifications_active_rounded,
          blocks: [
            LegalBlock.p('FlatCare sends push notifications for visitor requests, approvals, bills, notices and other '
                'society updates. Visitor alerts are sent with high priority because they are time sensitive.'),
            LegalBlock.bullets([
              'You can choose which notifications you receive under Notification Settings in the menu.',
              'You can also turn off notifications for FlatCare in your phone\'s settings.',
              'When you sign out, this device is removed from your account and stops receiving your alerts.',
            ]),
          ],
        ),
        LegalSection(
          title: 'Security of Your Data',
          icon: Icons.lock_rounded,
          blocks: [
            LegalBlock.p('We take reasonable technical and organisational steps to protect your information:'),
            LegalBlock.bullets([
              'All data travels between the app and our servers over encrypted (HTTPS) connections.',
              'Access to data is limited by role, so each person only sees what their role allows.',
              'Sign-in uses one-time passwords, so there is no password for anyone to steal.',
              'Each society\'s records are kept separate from every other society.',
            ]),
            LegalBlock.p('No method of storing or sending data over the internet is 100% secure, but we work '
                'continuously to protect your information and to fix any weakness we find.'),
          ],
        ),
        LegalSection(
          title: 'How Long We Keep Data',
          icon: Icons.history_rounded,
          blocks: [
            LegalBlock.p('We keep your information for as long as you are a member of your society on FlatCare and for '
                'as long as your society needs it — for example, payment records needed for the society\'s accounts '
                'and audits, or visitor logs needed for security.'),
            LegalBlock.p('When you move out or your account is closed, we delete or anonymise information that is no '
                'longer needed, unless the law requires us to keep it longer.'),
          ],
        ),
        LegalSection(
          title: 'Your Rights & Choices',
          icon: Icons.verified_user_rounded,
          blocks: [
            LegalBlock.p('You stay in control of your information. You can:'),
            LegalBlock.bullets([
              'View and update your profile, family members and vehicles in the app at any time.',
              'Ask for a copy of the personal information we hold about you.',
              'Ask us to correct information that is wrong or incomplete.',
              'Ask us to delete your information when you leave the society, subject to records your society must keep.',
              'Withdraw permission for notifications or your camera at any time from your phone\'s settings.',
            ]),
            LegalBlock.p('To make a request, contact your society office or FlatCare support using the details below.'),
          ],
        ),
        LegalSection(
          title: 'Children\'s Privacy',
          icon: Icons.child_care_rounded,
          blocks: [
            LegalBlock.p('FlatCare accounts are meant for adults. Parents may add children as family members so that '
                'the society knows who lives in the flat. We do not knowingly collect personal information directly '
                'from children under 18. If you believe a child has given us personal information without a '
                'parent\'s consent, please contact us and we will remove it.'),
          ],
        ),
        LegalSection(
          title: 'Changes to This Policy',
          icon: Icons.update_rounded,
          blocks: [
            LegalBlock.p('We may update this Privacy Policy from time to time. When we do, we will change the effective '
                'date at the top of this page and let you know through a notice in the app before any important '
                'change takes effect.'),
            LegalBlock.p('We encourage you to review this policy now and then. Changes take effect when they are '
                'published on this page.'),
          ],
        ),
        LegalSection(
          title: 'Contact Us',
          icon: Icons.support_agent_rounded,
          blocks: [
            LegalBlock.p('If you have any questions about this Privacy Policy or how your information is handled, '
                'please contact the FlatCare team by email or phone using the details below, or through Help Line '
                'in the app menu.'),
          ],
        ),
      ],
    );
  }
}

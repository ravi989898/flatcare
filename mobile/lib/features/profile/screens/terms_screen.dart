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
      summary: 'Please read these terms carefully. They explain the rules for using FlatCare in your society — what '
          'you can expect from us, and what we expect from every resident, committee member and security guard.',
      sections: [
        LegalSection(
          title: 'Your Acceptance',
          icon: Icons.handshake_rounded,
          blocks: [
            LegalBlock.p('These Terms & Conditions ("Terms") are an agreement between FlatCare ("FlatCare", "we", "us" '
                'or "our") and you ("User", "you" or "your"). They apply to your use of the FlatCare mobile app, the '
                'FlatCare website and every related service (together, the "Service").'),
            LegalBlock.p('By signing in to FlatCare, or by continuing to use it, you confirm that you have read, '
                'understood and agree to these Terms and to our Privacy Policy. If you do not agree, please do not '
                'use the Service and ask your society office to deactivate your account.'),
            LegalBlock.note('In short: FlatCare is a tool your society uses to run day-to-day life. Use it honestly, '
                'keep your login private, and respect other residents.'),
          ],
        ),
        LegalSection(
          title: 'About the FlatCare Service',
          icon: Icons.apartment_rounded,
          blocks: [
            LegalBlock.p('FlatCare is a society management platform that connects residents, the managing committee '
                'and security staff of a housing society. Depending on what your society has turned on, the Service '
                'lets you:'),
            LegalBlock.bullets([
              'Approve or reject visitors, pre-approve guests and share gate passes.',
              'View maintenance bills, pay them online and download receipts.',
              'Read notices, take part in polls and see upcoming events.',
              'Raise complaints and requests and follow them until they are resolved.',
              'Keep your household records — family members and vehicles — up to date.',
              'Find residents in the society directory and reach important contacts.',
            ]),
            LegalBlock.p('Features may differ from one society to another, and new features may be added over time.'),
          ],
        ),
        LegalSection(
          title: 'Your Account',
          icon: Icons.person_rounded,
          blocks: [
            LegalBlock.p('Accounts are created by your society admin for people who live in, own, or work for the '
                'society. You sign in with your registered mobile number and a one-time password (OTP).'),
            LegalBlock.numbered([
              'You must give correct information about yourself and your flat, and tell your society office if it changes.',
              'Never share your OTP with anyone — FlatCare staff and your society office will never ask for it.',
              'You are responsible for everything done through your account. If you think someone else has used it, '
                  'sign out and inform your society office or FlatCare support straight away.',
              'One account belongs to one person. Family members should have their own accounts where your society allows it.',
            ]),
          ],
        ),
        LegalSection(
          title: 'Your Society\'s Role',
          icon: Icons.groups_rounded,
          blocks: [
            LegalBlock.p('FlatCare provides the technology; your housing society decides how it is used. Your society '
                'admin and managing committee are responsible for:'),
            LegalBlock.bullets([
              'Adding, approving and removing residents, staff and security guards.',
              'Setting maintenance amounts, due dates, late fees and payment rules.',
              'Publishing notices, polls and events, and handling complaints.',
              'Following the society\'s own bye-laws and the housing laws that apply to it.',
            ]),
            LegalBlock.p('If you disagree with a decision taken by your society — for example the amount of a bill or '
                'the outcome of a complaint — please take it up with your society office. FlatCare cannot overrule '
                'your society\'s decisions.'),
          ],
        ),
        LegalSection(
          title: 'Permission to Use FlatCare',
          icon: Icons.verified_rounded,
          blocks: [
            LegalBlock.p('As long as you follow these Terms, we give you a personal, limited, non-transferable '
                'permission to use FlatCare for genuine society matters. This permission comes with a few conditions:'),
            LegalBlock.numbered([
              'Use the Service only for yourself and your household, not to run a business or offer it to others.',
              'Do not copy, sell, rent or distribute any part of the Service without our written permission.',
              'Do not try to reverse-engineer, decompile, change or build a copy of the app or its servers.',
              'Do not use robots, scrapers or automated tools to access the Service or collect information from it.',
            ]),
          ],
        ),
        LegalSection(
          title: 'Visitors & Gate Security',
          icon: Icons.shield_rounded,
          blocks: [
            LegalBlock.p('Visitor management only works when everyone uses it responsibly.'),
            LegalBlock.bullets([
              'Approve visitors only when you know or are expecting them. You are responsible for the people you let in.',
              'Gate passes and pre-approvals are for your own guests. Do not share them with anyone else.',
              'Security guards may still stop, question or refuse entry to any visitor for the safety of the society, '
                  'even if you have approved them.',
              'Visitor details, photos and entry times are recorded so that the society has a reliable entry log.',
            ]),
            LegalBlock.note('Emergency? Call the gate or your emergency contacts directly. Do not rely only on an app '
                'notification in urgent situations.'),
          ],
        ),
        LegalSection(
          title: 'Maintenance Bills & Payments',
          icon: Icons.receipt_long_rounded,
          blocks: [
            LegalBlock.p('Maintenance bills shown in FlatCare are raised by your society. FlatCare only displays them '
                'and helps you pay.'),
            LegalBlock.bullets([
              'Online payments are processed by our payment partner (Razorpay). Your card, UPI or bank details are '
                  'handled by them and are never stored by FlatCare.',
              'A payment is complete only after it is confirmed by the payment partner. The receipt then appears in the app.',
              'If money is deducted but the bill still shows unpaid, wait a few minutes and refresh. If it is still '
                  'unpaid, contact FlatCare support with your transaction reference.',
              'Refunds, waivers, late fees and disputes about the amount are decided by your society office.',
            ]),
          ],
        ),
        LegalSection(
          title: 'Content You Share',
          icon: Icons.chat_rounded,
          blocks: [
            LegalBlock.p('You may add content to FlatCare — for example complaints, photos, poll votes, family and '
                'vehicle details, or comments. You remain responsible for what you share.'),
            LegalBlock.bullets([
              'Only share information that is true and that you have the right to share.',
              'Do not share another person\'s private details without their permission.',
              'You allow FlatCare to store and show this content to the people in your society who need to see it, '
                  'only for the purpose of running the Service.',
              'Your society admin may remove content that is false, offensive or against society rules.',
            ]),
          ],
        ),
        LegalSection(
          title: 'Things You Must Not Do',
          icon: Icons.block_rounded,
          blocks: [
            LegalBlock.p('To keep FlatCare safe and fair for everyone, you agree that you will not:'),
            LegalBlock.bullets([
              'Post false complaints, abusive or hateful language, spam or advertisements.',
              'Try to view, change or delete another resident\'s data.',
              'Pretend to be another resident, a committee member or a security guard.',
              'Misuse visitor approvals or try to get around the society\'s gate security.',
              'Upload viruses or harmful code, or try to overload, test or break into our servers.',
              'Collect phone numbers or other personal details from the directory for marketing or any purpose '
                  'unrelated to society life.',
            ]),
          ],
        ),
        LegalSection(
          title: 'Availability & Updates',
          icon: Icons.system_update_rounded,
          blocks: [
            LegalBlock.p('We work hard to keep FlatCare running smoothly, but we cannot promise that it will always be '
                'available or free of errors. The Service may be briefly unavailable during maintenance, updates, '
                'or because of problems with the internet, mobile networks or third-party services.'),
            LegalBlock.p('From time to time we release app updates to add features, improve security and fix issues. '
                'Some older versions may stop working, so please keep the app updated.'),
          ],
        ),
        LegalSection(
          title: 'Ownership',
          icon: Icons.copyright_rounded,
          blocks: [
            LegalBlock.p('The FlatCare name, logo, app design, software and all related material belong to FlatCare '
                'and are protected by law. Using the Service does not give you ownership of any of it.'),
            LegalBlock.p('Your society\'s records and the content you add belong to you and your society. We use them '
                'only to provide the Service, as explained in our Privacy Policy.'),
          ],
        ),
        LegalSection(
          title: 'Limitation of Liability',
          icon: Icons.balance_rounded,
          blocks: [
            LegalBlock.p('FlatCare is provided "as is". To the extent the law allows:'),
            LegalBlock.bullets([
              'We are not responsible for decisions made by your society, its committee or its staff.',
              'We are not responsible for the actions of visitors, residents or security guards.',
              'We are not liable for indirect losses, or for losses caused by events outside our reasonable control, '
                  'such as network failures, power cuts or natural disasters.',
            ]),
            LegalBlock.p('Nothing in these Terms limits any rights you have under Indian consumer protection law.'),
          ],
        ),
        LegalSection(
          title: 'Suspension & Termination',
          icon: Icons.person_off_rounded,
          blocks: [
            LegalBlock.p('Your society admin or FlatCare may suspend or close your account if you break these Terms, '
                'if you move out of the society, or if your society stops using FlatCare.'),
            LegalBlock.p('You can stop using FlatCare at any time by signing out and asking your society office to '
                'deactivate your account. Records the society needs for its accounts, such as payment history, may '
                'be kept as explained in our Privacy Policy.'),
          ],
        ),
        LegalSection(
          title: 'Changes & Amendments',
          icon: Icons.update_rounded,
          blocks: [
            LegalBlock.p('We may update these Terms as FlatCare grows or when the law changes. When we do, we will '
                'change the effective date at the top of this page, and we will tell you in the app before any '
                'important change takes effect.'),
            LegalBlock.p('If you continue to use FlatCare after the updated Terms take effect, it means you accept them.'),
          ],
        ),
        LegalSection(
          title: 'Governing Law',
          icon: Icons.account_balance_rounded,
          blocks: [
            LegalBlock.p('These Terms are governed by the laws of India. We always prefer to solve problems by talking '
                'first — please contact us and we will do our best to help. Any dispute that cannot be settled this '
                'way will be handled by the competent courts in India.'),
          ],
        ),
        LegalSection(
          title: 'Contacting Us',
          icon: Icons.support_agent_rounded,
          blocks: [
            LegalBlock.p('If you have any questions about these Terms, or anything in the app, you can reach the '
                'FlatCare team through Help Line in the menu, or using the contact details below. For questions '
                'about your bills, flat or society rules, please contact your society office.'),
          ],
        ),
      ],
    );
  }
}

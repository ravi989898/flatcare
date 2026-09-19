import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/theme/app_theme.dart';
import '../../auth/providers/auth_provider.dart';

/// FlatCare customer-service contacts — the same numbers and email shown in
/// the website footer, so an app user and a website visitor reach the same
/// team.
class SupportContacts {
  SupportContacts._();

  static const phones = ['9664653896', '9712423633'];
  static const email = 'support@flatcare.in';

  /// "9664653896" -> "+91 96646 53896"
  static String pretty(String phone) => '+91 ${phone.substring(0, 5)} ${phone.substring(5)}';
}

/// "Help Line" — 24/7 customer service. Every card is one tap to call,
/// WhatsApp or e-mail, and the topic chips open WhatsApp with the resident's
/// name, society and flat already typed in so support can help right away.
class HelpLineScreen extends ConsumerWidget {
  const HelpLineScreen({super.key});

  static const _topics = <(IconData, String, Color)>[
    (Icons.login_rounded, 'Login or OTP problem', Color(0xFF1E9CE0)),
    (Icons.receipt_long_rounded, 'Bills & payments', Color(0xFF2AB930)),
    (Icons.people_alt_rounded, 'Visitors & gate pass', Color(0xFF8E5FE0)),
    (Icons.build_rounded, 'Complaint or request', Color(0xFFF5A623)),
    (Icons.phone_android_rounded, 'App is not working', Color(0xFFE0245E)),
    (Icons.chat_bubble_outline_rounded, 'Something else', Color(0xFF6B7280)),
  ];

  Future<void> _open(BuildContext context, Uri uri) async {
    final ok = await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!ok && context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Couldn't open that. Please try another way to reach us.")));
    }
  }

  Uri _whatsApp(String phone, String message) => Uri.parse('https://wa.me/91$phone?text=${Uri.encodeComponent(message)}');

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authControllerProvider).valueOrNull;
    final name = auth?.user.name ?? '';
    final society = auth?.society.name ?? '';
    final unit = auth?.user.primaryResidency?.flat.displayLabel ?? '';

    final who = [
      if (name.isNotEmpty) "I'm $name",
      if (society.isNotEmpty) 'from $society',
      if (unit.isNotEmpty) '($unit)',
    ].join(' ');

    String message(String topic) => 'Hello FlatCare Support, ${who.isEmpty ? '' : '$who. '}I need help with: $topic';

    return Scaffold(
      backgroundColor: AppTheme.pageBackground,
      body: CustomScrollView(
        slivers: [
          SliverToBoxAdapter(child: _Header(onBack: () => Navigator.of(context).maybePop())),
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(16, 20, 16, 32),
            sliver: SliverList.list(
              children: [
                const _SectionTitle('Talk to us right now'),
                _ContactCard(
                  icon: Icons.call_rounded,
                  color: AppTheme.brandBlue,
                  title: 'Call customer care',
                  children: [
                    for (final phone in SupportContacts.phones)
                      _ContactRow(
                        label: SupportContacts.pretty(phone),
                        actionIcon: Icons.call_rounded,
                        actionLabel: 'Call',
                        onTap: () => _open(context, Uri.parse('tel:+91$phone')),
                      ),
                  ],
                ),
                const SizedBox(height: 12),
                _ContactCard(
                  icon: Icons.chat_rounded,
                  color: const Color(0xFF25A244),
                  title: 'Chat on WhatsApp',
                  children: [
                    for (final phone in SupportContacts.phones)
                      _ContactRow(
                        label: SupportContacts.pretty(phone),
                        actionIcon: Icons.send_rounded,
                        actionLabel: 'Chat',
                        onTap: () => _open(context, _whatsApp(phone, message('an app question'))),
                      ),
                  ],
                ),
                const SizedBox(height: 12),
                _ContactCard(
                  icon: Icons.email_rounded,
                  color: const Color(0xFF8E5FE0),
                  title: 'Send us an email',
                  children: [
                    _ContactRow(
                      label: SupportContacts.email,
                      actionIcon: Icons.mail_outline_rounded,
                      actionLabel: 'Email',
                      onTap: () => _open(
                        context,
                        Uri(
                          scheme: 'mailto',
                          path: SupportContacts.email,
                          query: 'subject=${Uri.encodeComponent('FlatCare app help')}&body=${Uri.encodeComponent(message('\n'))}',
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 24),
                const _SectionTitle('What do you need help with?'),
                const Padding(
                  padding: EdgeInsets.only(bottom: 12),
                  child: Text('Pick a topic and we will open WhatsApp with your details filled in.', style: TextStyle(color: Colors.black54, fontSize: 13)),
                ),
                Wrap(
                  spacing: 10,
                  runSpacing: 10,
                  children: [
                    for (final (icon, label, color) in _topics)
                      ActionChip(
                        avatar: Icon(icon, size: 18, color: color),
                        label: Text(label),
                        backgroundColor: Colors.white,
                        side: BorderSide(color: color.withValues(alpha: 0.35)),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(999)),
                        onPressed: () => _open(context, _whatsApp(SupportContacts.phones.first, message(label))),
                      ),
                  ],
                ),
                const SizedBox(height: 24),
                Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16)),
                  child: const Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Icon(Icons.info_outline_rounded, color: AppTheme.brandBlue, size: 20),
                      SizedBox(width: 10),
                      Expanded(
                        child: Text(
                          'For a problem inside your society (water, lift, security), please use Complaints or Important Contacts. This help line is for questions about the FlatCare app.',
                          style: TextStyle(fontSize: 12.5, color: Colors.black54, height: 1.4),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _Header extends StatelessWidget {
  const _Header({required this.onBack});

  final VoidCallback onBack;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      decoration: const BoxDecoration(
        gradient: AppTheme.brandGradient,
        borderRadius: BorderRadius.vertical(bottom: Radius.circular(32)),
      ),
      child: SafeArea(
        bottom: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(4, 4, 16, 28),
          child: Column(
            children: [
              Align(
                alignment: Alignment.centerLeft,
                child: IconButton(
                  onPressed: onBack,
                  tooltip: 'Back',
                  icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white, size: 20),
                ),
              ),
              Container(
                width: 88,
                height: 88,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: Colors.white,
                  boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.15), blurRadius: 18, offset: const Offset(0, 8))],
                ),
                child: const Icon(Icons.support_agent_rounded, size: 50, color: AppTheme.brandBlue),
              ),
              const SizedBox(height: 14),
              const Text('Help Line', style: TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.w800)),
              const SizedBox(height: 6),
              const Text(
                'Any question about the FlatCare app?\nTalk to a real person, any time.',
                textAlign: TextAlign.center,
                style: TextStyle(color: Colors.white, fontSize: 14, height: 1.4),
              ),
              const SizedBox(height: 14),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
                decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(999)),
                child: const Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(Icons.circle, size: 10, color: Color(0xFF2AB930)),
                    SizedBox(width: 8),
                    Text('Customer service  •  24 × 7', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13, color: AppTheme.brandNavy)),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  const _SectionTitle(this.text);

  final String text;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10, left: 2),
      child: Text(text, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: AppTheme.brandNavy)),
    );
  }
}

class _ContactCard extends StatelessWidget {
  const _ContactCard({required this.icon, required this.color, required this.title, required this.children});

  final IconData icon;
  final Color color;
  final String title;
  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        boxShadow: const [BoxShadow(color: Color(0x140B3B66), blurRadius: 14, offset: Offset(0, 4))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(color: color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(12)),
                child: Icon(icon, color: color, size: 22),
              ),
              const SizedBox(width: 12),
              Text(title, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15.5)),
            ],
          ),
          const SizedBox(height: 8),
          ...children,
        ],
      ),
    );
  }
}

class _ContactRow extends StatelessWidget {
  const _ContactRow({required this.label, required this.actionIcon, required this.actionLabel, required this.onTap});

  final String label;
  final IconData actionIcon;
  final String actionLabel;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 6),
      child: Row(
        children: [
          Expanded(
            child: SelectableText(label, style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w600, color: AppTheme.brandNavy)),
          ),
          FilledButton.tonalIcon(
            onPressed: onTap,
            icon: Icon(actionIcon, size: 18),
            label: Text(actionLabel),
            style: FilledButton.styleFrom(
              minimumSize: const Size(0, 44),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
          ),
        ],
      ),
    );
  }
}
